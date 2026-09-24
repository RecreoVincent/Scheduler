<?php

namespace App\Http\Controllers\Dean;

use App\Exceptions\ScheduleGenerationException;
use App\Models\AcademicSection;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassScheduleGenerator;
use App\Services\ScheduleNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends DeanController
{
    public function __construct(
        private readonly ClassScheduleGenerator $generator,
        private readonly ScheduleNotificationService $notifications,
    ) {}

    public function create(Request $request): View
    {
        $course = $this->course($request);
        $sections = AcademicSection::forDepartment($course)
            ->orderByDesc('academic_year')
            ->orderBy('year_level')
            ->orderBy('name')
            ->get();
        $enabledSemesters = $this->enabledSemesters($request);

        return view('dean.schedules.create', compact('course', 'sections', 'enabledSemesters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', Rule::in($this->enabledSemesters($request))],
            'curriculum' => ['nullable', Rule::in(['New', 'Old'])],
            // Keep accepting the former scalar field for old links/tests while
            // the form now submits a checkbox-based list.
            'year_level' => ['nullable', Rule::in(['all', '1', '2', '3', '4'])],
            'year_levels' => ['nullable', 'array', 'min:1'],
            'year_levels.*' => [Rule::in(['1', '2', '3', '4'])],
            'number_of_sections' => ['nullable', 'integer', 'between:1,20'],
        ]);
        $validated['curriculum'] ??= 'New';
        $selectedYearLevels = collect($validated['year_levels'] ?? [])
            ->map(fn ($level): int => (int) $level)
            ->filter(fn (int $level): bool => $level >= 1 && $level <= 4)
            ->unique()
            ->sort()
            ->values();

        if ($selectedYearLevels->isEmpty() && filled($validated['year_level'] ?? null)) {
            $selectedYearLevels = $validated['year_level'] === 'all'
                ? collect(range(1, 4))
                : collect([(int) $validated['year_level']]);
        }

        if ($selectedYearLevels->isEmpty()) {
            throw ValidationException::withMessages(['year_levels' => 'Select at least one year level.']);
        }

        $singleYearLevel = $selectedYearLevels->count() === 1;
        if ($singleYearLevel && blank($validated['number_of_sections'] ?? null)) {
            throw ValidationException::withMessages(['number_of_sections' => 'Enter the number of existing sections to use.']);
        }

        $course = $this->course($request);
        $availableSections = AcademicSection::query()
            ->forDepartment($course)
            ->where('academic_year', $validated['academic_year'])
            ->whereIn('year_level', $selectedYearLevels)
            ->get()
            ->sortBy(
                fn (AcademicSection $section): string => sprintf('%02d-%s', $section->year_level, $section->name),
                SORT_NATURAL | SORT_FLAG_CASE,
            )
            ->values();

        if ($availableSections->isEmpty()) {
            throw ValidationException::withMessages([
                'year_level' => 'No existing sections are available for the selected academic year and year level.',
            ]);
        }

        if ($singleYearLevel) {
            if ($availableSections->count() < $validated['number_of_sections']) {
                throw ValidationException::withMessages([
                    'number_of_sections' => "Only {$availableSections->count()} existing section(s) are available for the selected academic year and year level.",
                ]);
            }

            $sections = $availableSections->take($validated['number_of_sections'])->values();
        } else {
            $sections = $availableSections;
        }

        $selectedYearLevels = $sections->pluck('year_level')->unique()->values();
        $subjects = Subject::with('instructors')
            ->forDepartment($course)
            ->whereIn('year_level', $selectedYearLevels)
            ->where('semester', $validated['semester'])
            ->where('curriculum', $validated['curriculum'])
            ->where('classification', 'Major')
            ->get();
        $yearWithoutSubjects = $selectedYearLevels->first(
            fn ($yearLevel): bool => $subjects->where('year_level', $yearLevel)->isEmpty(),
        );

        if ($yearWithoutSubjects !== null) {
            throw ValidationException::withMessages([
                'year_level' => "No {$validated['semester']} semester {$validated['curriculum']} Curriculum Major subjects are available for Year {$yearWithoutSubjects}.",
            ]);
        }

        $rooms = Room::forDepartment($course)->orderBy('name')->get();
        $instructors = User::forDepartment($course)->where('role', 'instructor')->where('account_status', 'active')->get();

        $assignedInstructors = $subjects->flatMap->instructors->where('account_status', 'active');
        $subjectsRequireRooms = $subjects->contains(
            fn (Subject $subject): bool => ! $this->generator->subjectCanUseTba($course, $subject),
        );

        if ($subjects->isEmpty() || ($subjectsRequireRooms && $rooms->isEmpty()) || ($instructors->isEmpty() && $assignedInstructors->isEmpty())) {
            return $this->failureResponse('The schedule cannot be created because a required subject, compatible room, or active instructor is missing.');
        }

        try {
            $created = $this->generator->generate($course, $sections, $subjects, $rooms, $instructors, $validated);
        } catch (ScheduleGenerationException $exception) {
            return $this->failureResponse(
                $exception->getMessage().' No schedules were changed.',
                $exception->guidance,
            );
        } catch (\RuntimeException $exception) {
            return $this->failureResponse($exception->getMessage().' No schedules were changed.');
        }

        $this->notifications->schedulesGenerated($sections, $validated);

        return redirect()->route('dean.timetable.index', $request->only(['academic_year', 'semester']))->with('success', "{$created} class schedule entries generated successfully.");
    }

    private function failureResponse(string $message, ?string $guidance = null): RedirectResponse
    {
        return back()
            ->withInput()
            ->with('error', $message)
            ->with('error_note', $guidance ?? $this->failureGuidance($message));
    }

    private function failureGuidance(string $message): string
    {
        return match (true) {
            str_contains($message, 'every allowed day and time') => 'Check the section timetable for occupied periods, assign another instructor who still has available workload hours, and verify that a compatible room is free. If resources are limited, generate fewer sections at one time.',
            str_contains($message, 'No assigned instructor has enough workload capacity') => 'Every instructor assigned to this subject has reached or would exceed their configured workload-hour limit. Assign an instructor with remaining workload hours or adjust the limit on the Instructor Units page.',
            str_contains($message, 'room is available'), str_contains($message, 'matching room') => 'There are not enough rooms of the required type for the selected sections. Add a compatible room, free an occupied room period, or generate fewer sections.',
            str_contains($message, 'First Year section') => 'First-year schedules must include the M–W, T–Th, and F–S meeting patterns. Add the missing Major subjects for the affected day pattern and make sure instructors and rooms are available on those days.',
            str_contains($message, 'workload hours') => 'The generated load exceeds the instructor’s configured maximum workload hours. Reduce the assigned load or adjust the maximum on the Instructor Units page; instructors do not need to use all available hours.',
            str_contains($message, 'required subject, compatible room, or active instructor is missing') => 'Review the Subjects, Subject Assignment, Instructor List, and Rooms pages. Complete the missing information, then create the schedule again.',
            default => 'Review the selected academic period, sections, subject assignments, instructor unit limits, room types, and existing timetable conflicts before trying again.',
        };
    }

}
