<?php

namespace App\Http\Controllers\Dean;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\User;
use App\Services\ClassScheduleGenerator;
use App\Services\ScheduleNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ScheduleArchiveController extends DeanController
{
    public function __construct(
        private readonly ClassScheduleGenerator $schedulingRules,
        private readonly ScheduleNotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $course = $this->course($request);
        $enabledSemesters = $this->enabledSemesters($request);
        $archiveQuery = ClassSchedule::onlyTrashed()
            ->forDepartment($course)
            ->whereHas('subject', fn ($subjectQuery) => $subjectQuery->where('classification', 'Major'))
            ->whereIn('semester', $enabledSemesters);
        $academicYears = (clone $archiveQuery)
            ->select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');
        $deletionDates = (clone $archiveQuery)
            ->selectRaw('DATE(deleted_at) as deletion_date')
            ->distinct()
            ->orderByDesc('deletion_date')
            ->pluck('deletion_date');
        $query = clone $archiveQuery;

        foreach (['academic_year', 'semester'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, (string) $request->string($filter));
            }
        }

        if ($request->filled('deleted_on')) {
            $query->whereDate('deleted_at', (string) $request->string('deleted_on'));
        }

        $archivePages = (clone $query)
            ->select(['section_id', 'academic_year', 'semester'])
            ->selectRaw('DATE(deleted_at) as deletion_date')
            ->groupBy('section_id', 'academic_year', 'semester')
            ->groupByRaw('DATE(deleted_at)')
            ->orderByRaw('DATE(deleted_at) DESC')
            ->orderByDesc('academic_year')
            ->orderByRaw("CASE semester WHEN '1st' THEN 1 WHEN '2nd' THEN 2 WHEN 'Summer' THEN 3 ELSE 4 END")
            ->orderBy('section_id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $sectionsById = AcademicSection::query()
            ->forDepartment($course)
            ->whereIn('id', $archivePages->getCollection()->pluck('section_id'))
            ->get()
            ->keyBy('id');

        $archivedSchedulesByGroup = collect();

        if ($archivePages->isNotEmpty()) {
            $archivedSchedulesByGroup = (clone $query)
                ->with(['subject', 'instructor', 'room'])
                ->where(function ($groupQuery) use ($archivePages) {
                    foreach ($archivePages as $archiveGroup) {
                        $groupQuery->orWhere(function ($periodQuery) use ($archiveGroup) {
                            $periodQuery
                                ->where('section_id', $archiveGroup->section_id)
                                ->where('academic_year', $archiveGroup->academic_year)
                                ->where('semester', $archiveGroup->semester)
                                ->whereDate('deleted_at', $archiveGroup->deletion_date);
                        });
                    }
                })
                ->orderByRaw(ClassSchedule::dayOrderSql())
                ->orderBy('start_time')
                ->get()
                ->groupBy(fn (ClassSchedule $schedule): string => $this->archiveGroupKey(
                    $schedule->section_id,
                    $schedule->academic_year,
                    $schedule->semester,
                    $schedule->deleted_at->toDateString(),
                ));
        }

        $deletedInstructors = User::onlyTrashed()
            ->forDepartment($course)
            ->where('role', 'instructor')
            ->orderByDesc('deleted_at')
            ->get();

        $deletedStudents = User::onlyTrashed()
            ->forDepartment($course)
            ->where('role', 'student')
            ->orderByDesc('deleted_at')
            ->get();

        return view('dean.archive.index', compact(
            'course',
            'academicYears',
            'deletionDates',
            'archivePages',
            'sectionsById',
            'archivedSchedulesByGroup',
            'enabledSemesters',
            'deletedInstructors',
            'deletedStudents',
        ));
    }

    public function restore(Request $request, int $schedule): RedirectResponse
    {
        $schedule = $this->archivedSchedule($request, $schedule);
        $schedule->loadMissing(['section', 'subject', 'instructor', 'room']);

        if (! $schedule->section || ! $schedule->subject || ! $schedule->instructor || ($schedule->room_id !== null && ! $schedule->room)) {
            return back()->with('error', 'This schedule cannot be restored because one of its related records no longer exists.');
        }

        $ruleViolation = $this->schedulingRules->assignmentRuleViolation(
            $schedule,
            $schedule->instructor,
            $schedule->room,
            $schedule->day,
            substr($schedule->start_time, 0, 5),
            substr($schedule->end_time, 0, 5),
        );

        if ($ruleViolation !== null) {
            return back()->with('error', 'Schedule not restored: '.$ruleViolation);
        }

        $conflict = ClassSchedule::query()
            ->where('academic_year', $schedule->academic_year)
            ->where('semester', $schedule->semester)
            ->whereIn('day', ClassSchedule::conflictingDayPatterns($schedule->day))
            ->where(function ($query) use ($schedule) {
                $query->where('section_id', $schedule->section_id)
                    ->orWhere('instructor_id', $schedule->instructor_id);

                if ($schedule->room_id !== null) {
                    $query->orWhere('room_id', $schedule->room_id);
                }
            })
            ->where('start_time', '<', $schedule->end_time)
            ->where('end_time', '>', $schedule->start_time)
            ->exists();

        if ($conflict) {
            return back()->with('error', 'Schedule not restored because its section, instructor, or room now has a conflicting class.');
        }

        $schedule->restore();
        $this->notifications->scheduleRestored($schedule);

        return back()->with('success', 'Class schedule restored successfully.');
    }

    public function destroy(Request $request, int $schedule): RedirectResponse
    {
        $schedule = $this->archivedSchedule($request, $schedule);

        try {
            $schedule->forceDelete();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The archived class schedule could not be permanently deleted.');
        }

        return back()->with('success', 'Archived class schedule permanently deleted.');
    }

    public function destroySection(Request $request, AcademicSection $section): RedirectResponse
    {
        $this->ensureCourse($request, $section);
        $validated = $request->validate([
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
            'deleted_on' => ['required', 'date_format:Y-m-d'],
        ]);

        try {
            $deleted = ClassSchedule::onlyTrashed()
                ->forDepartment($this->course($request))
                ->whereHas('subject', fn ($subjectQuery) => $subjectQuery->where('classification', 'Major'))
                ->where('section_id', $section->id)
                ->where('academic_year', $validated['academic_year'])
                ->where('semester', $validated['semester'])
                ->whereDate('deleted_at', $validated['deleted_on'])
                ->forceDelete();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The archived section schedule could not be permanently deleted.');
        }

        if ($deleted === 0) {
            return back()->with('error', 'No archived class schedules were found for this section.');
        }

        return back()->with(
            'success',
            "{$section->name} {$validated['academic_year']} {$validated['semester']} Semester archive from "
                .date('F j, Y', strtotime($validated['deleted_on']))
                ." permanently deleted ({$deleted} class ".str('entry')->plural($deleted).').',
        );
    }

    public function restoreAccount(Request $request, int $user): RedirectResponse
    {
        $deletedInstructor = $this->archivedInstructor($request, $user);
        $deletedInstructor->restore();

        return back()->with('success', 'Instructor account restored successfully.');
    }

    public function destroyAccount(Request $request, int $user): RedirectResponse
    {
        $deletedInstructor = $this->archivedInstructor($request, $user);

        try {
            DB::table('subject_instructor')->where('instructor_id', $deletedInstructor->id)->delete();
            ClassSchedule::withTrashed()->where('instructor_id', $deletedInstructor->id)->forceDelete();
            $deletedInstructor->forceDelete();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The instructor account could not be permanently deleted.');
        }

        return back()->with('success', 'Instructor account permanently deleted.');
    }

    public function destroyAllAccounts(Request $request): RedirectResponse
    {
        $course = $this->course($request);
        $instructorIds = User::onlyTrashed()->forDepartment($course)->where('role', 'instructor')->pluck('id');

        if ($instructorIds->isEmpty()) {
            return back()->with('error', "There are no deleted {$course} instructor accounts to remove.");
        }

        try {
            DB::table('subject_instructor')->whereIn('instructor_id', $instructorIds)->delete();
            ClassSchedule::withTrashed()->whereIn('instructor_id', $instructorIds)->forceDelete();
            $removedCount = User::onlyTrashed()->whereIn('id', $instructorIds)->forceDelete();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The deleted instructor accounts could not be permanently removed.');
        }

        return redirect()->route('dean.archive.index')->with(
            'success',
            "All deleted {$course} instructor accounts were permanently removed ({$removedCount} ".str('account')->plural($removedCount).').',
        );
    }

    private function archivedInstructor(Request $request, int $user): User
    {
        return User::onlyTrashed()
            ->forDepartment($this->course($request))
            ->where('role', 'instructor')
            ->findOrFail($user);
    }

    public function restoreStudentAccount(Request $request, int $user): RedirectResponse
    {
        $deletedStudent = $this->archivedStudent($request, $user);
        $deletedStudent->restore();

        return back()->with('success', 'Student account restored successfully.');
    }

    public function destroyStudentAccount(Request $request, int $user): RedirectResponse
    {
        $deletedStudent = $this->archivedStudent($request, $user);

        try {
            $deletedStudent->forceDelete();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The student account could not be permanently deleted.');
        }

        return back()->with('success', 'Student account permanently deleted.');
    }

    public function destroyAllStudentAccounts(Request $request): RedirectResponse
    {
        $course = $this->course($request);
        $studentIds = User::onlyTrashed()->forDepartment($course)->where('role', 'student')->pluck('id');

        if ($studentIds->isEmpty()) {
            return back()->with('error', "There are no deleted {$course} student accounts to remove.");
        }

        try {
            $removedCount = User::onlyTrashed()->whereIn('id', $studentIds)->forceDelete();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The deleted student accounts could not be permanently removed.');
        }

        return redirect()->route('dean.archive.index')->with(
            'success',
            "All deleted {$course} student accounts were permanently removed ({$removedCount} ".str('account')->plural($removedCount).').',
        );
    }

    private function archivedStudent(Request $request, int $user): User
    {
        return User::onlyTrashed()
            ->forDepartment($this->course($request))
            ->where('role', 'student')
            ->findOrFail($user);
    }

    private function archiveGroupKey(int $sectionId, string $academicYear, string $semester, string $deletionDate): string
    {
        return "{$deletionDate}|{$academicYear}|{$semester}|{$sectionId}";
    }

    private function archivedSchedule(Request $request, int $schedule): ClassSchedule
    {
        return ClassSchedule::onlyTrashed()
            ->forDepartment($this->course($request))
            ->whereHas('subject', fn ($subjectQuery) => $subjectQuery->where('classification', 'Major'))
            ->findOrFail($schedule);
    }
}
