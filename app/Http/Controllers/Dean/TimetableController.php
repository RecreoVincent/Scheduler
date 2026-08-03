<?php

namespace App\Http\Controllers\Dean;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\Room;
use App\Models\User;
use App\Services\ClassScheduleGenerator;
use App\Services\ScheduleNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TimetableController extends DeanController
{
    public function __construct(
        private readonly ClassScheduleGenerator $schedulingRules,
        private readonly ScheduleNotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $course = $this->course($request);
        $query = ClassSchedule::query()->where('course', $course);

        foreach (['section_id', 'academic_year', 'semester', 'day'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('year_level')) {
            $query->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('year_level', (int) $request->input('year_level')));
        }

        $filteredScheduleCount = (clone $query)->count();

        $scheduledSectionIds = (clone $query)->distinct()->pluck('section_id');
        $sectionPages = AcademicSection::query()
            ->where('course', $course)
            ->whereIn('id', $scheduledSectionIds)
            ->orderBy('year_level')
            ->orderBy('name')
            ->paginate(6)
            ->withQueryString();

        $schedulesBySection = (clone $query)
            ->with(['subject', 'instructor', 'room'])
            ->whereIn('section_id', $sectionPages->getCollection()->pluck('id'))
            ->orderByRaw(ClassSchedule::dayOrderSql())
            ->orderBy('start_time')
            ->get()
            ->groupBy('section_id');

        $sections = AcademicSection::where('course', $course)->orderBy('year_level')->orderBy('name')->get();

        return view('dean.timetable.index', compact('course', 'sectionPages', 'schedulesBySection', 'sections', 'filteredScheduleCount'));
    }

    public function edit(Request $request, ClassSchedule $timetable): View
    {
        $this->ensureCourse($request, $timetable);
        $course = $this->course($request);
        $rooms = Room::where('course', $course)->orderBy('name')->get();
        $instructors = User::where('role', 'instructor')->where('account_status', 'active')->orderBy('course')->orderBy('first_name')->get();

        return view('dean.timetable.edit', compact('course', 'timetable', 'rooms', 'instructors'));
    }

    public function update(Request $request, ClassSchedule $timetable): RedirectResponse
    {
        $this->ensureCourse($request, $timetable);
        $validated = $request->validate([
            'instructor_id' => ['required', 'integer'], 'room_id' => ['required', 'integer'],
            'day' => ['required', Rule::in(array_keys(ClassSchedule::DAY_PATTERNS))],
            'start_time' => ['required', 'date_format:H:i'], 'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);
        $course = $this->course($request);
        abort_unless(Room::whereKey($validated['room_id'])->where('course', $course)->exists(), 422);
        abort_unless(User::whereKey($validated['instructor_id'])->where('role', 'instructor')->where('account_status', 'active')->exists(), 422);

        $room = Room::findOrFail($validated['room_id']);
        $instructor = User::findOrFail($validated['instructor_id']);
        $timetable->loadMissing('subject');
        $ruleViolation = $this->schedulingRules->assignmentRuleViolation(
            $timetable,
            $instructor,
            $room,
            $validated['day'],
            $validated['start_time'],
            $validated['end_time'],
        );

        if ($ruleViolation !== null) {
            return back()->withInput()->with('error', $ruleViolation);
        }

        $conflict = ClassSchedule::whereKeyNot($timetable->id)
            ->where('academic_year', $timetable->academic_year)->where('semester', $timetable->semester)->whereIn('day', ClassSchedule::conflictingDayPatterns($validated['day']))
            ->where(fn ($q) => $q->where('section_id', $timetable->section_id)->orWhere('instructor_id', $validated['instructor_id'])->orWhere('room_id', $validated['room_id']))
            ->where('start_time', '<', $validated['end_time'])->where('end_time', '>', $validated['start_time'])->exists();

        if ($conflict) {
            return back()->withInput()->with('error', 'That time conflicts with an existing section, instructor, or room schedule.');
        }

        $previousInstructorId = $timetable->instructor_id;
        $timetable->update($validated);
        $timetable->refresh();
        $this->notifications->scheduleUpdated($timetable, $previousInstructorId);

        return redirect()->route('dean.timetable.index')->with('success', 'Class schedule updated successfully.');
    }

    public function destroy(Request $request, ClassSchedule $timetable): RedirectResponse
    {
        $this->ensureCourse($request, $timetable);
        $timetable->loadMissing(['section', 'subject', 'room']);
        $timetable->delete();
        $this->notifications->scheduleArchived($timetable);

        return back()->with('success', 'Class schedule moved to the archive successfully.');
    }

    public function destroySection(Request $request, AcademicSection $section): RedirectResponse
    {
        $this->ensureCourse($request, $section);

        $schedules = ClassSchedule::where('course', $this->course($request))
            ->where('section_id', $section->id)
            ->get();
        $deleted = $schedules->isEmpty()
            ? 0
            : ClassSchedule::whereIn('id', $schedules->pluck('id'))->delete();
        $this->notifications->sectionArchived($section, $schedules);

        return back()->with(
            'success',
            "{$section->name} schedule moved to the archive successfully ({$deleted} class ".str('entry')->plural($deleted).').',
        );
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'section_id' => ['nullable', 'integer'],
            'year_level' => ['nullable', 'integer', 'between:1,4'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', Rule::in(['1st', '2nd', 'Summer'])],
            'day' => ['nullable', Rule::in(array_keys(ClassSchedule::DAY_PATTERNS))],
        ]);

        $course = $this->course($request);
        $query = ClassSchedule::query()->where('course', $course);

        foreach (['section_id', 'academic_year', 'semester', 'day'] as $filter) {
            if (filled($validated[$filter] ?? null)) {
                $query->where($filter, $validated[$filter]);
            }
        }

        if (filled($validated['year_level'] ?? null)) {
            $query->whereHas('section', fn ($sectionQuery) => $sectionQuery->where('year_level', $validated['year_level']));
        }

        $schedules = $query->with('section')->get();

        if ($schedules->isEmpty()) {
            return back()->with('error', 'There are no active schedules matching the current timetable filters. Nothing was deleted.');
        }

        $sections = $schedules->pluck('section')->filter()->unique('id')->values();
        ClassSchedule::whereIn('id', $schedules->pluck('id'))->delete();
        $this->notifications->schedulesArchived($schedules);

        $entryCount = $schedules->count();
        $sectionCount = $sections->count();

        return back()->with(
            'success',
            "All matching schedules were moved to the archive successfully ({$entryCount} class ".str('entry')->plural($entryCount)." across {$sectionCount} ".str('section')->plural($sectionCount).').',
        );
    }
}
