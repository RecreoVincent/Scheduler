<?php

namespace App\Http\Controllers\Gec;

use App\Exceptions\ScheduleGenerationException;
use App\Models\AcademicSection;
use App\Models\Room;
use App\Models\User;
use App\Services\ClassScheduleGenerator;
use App\Services\ScheduleNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends GecController
{
    public function __construct(
        private readonly ClassScheduleGenerator $generator,
        private readonly ScheduleNotificationService $notifications,
    ) {}

    public function create(Request $request): View
    {
        $department = $request->input('department');
        $department = in_array($department, self::REAL_DEPARTMENTS, true) ? $department : self::REAL_DEPARTMENTS[0];

        $sections = AcademicSection::forDepartment($department)
            ->orderByDesc('academic_year')
            ->orderBy('year_level')
            ->orderBy('name')
            ->get();
        $enabledSemesters = $this->enabledSemesters($request);

        return view('gec.schedules.create', compact('department', 'sections', 'enabledSemesters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department' => ['required', Rule::in(self::REAL_DEPARTMENTS)],
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', Rule::in($this->enabledSemesters($request))],
            'curriculum' => ['nullable', Rule::in(['New', 'Old'])],
            'year_levels' => ['nullable', 'array', 'min:1'],
            'year_levels.*' => [Rule::in(['1', '2', '3', '4'])],
            'number_of_sections' => ['nullable', 'integer', 'between:1,20'],
        ]);
        $validated['curriculum'] ??= 'New';
        $department = $validated['department'];
        $selectedYearLevels = collect($validated['year_levels'] ?? [])
            ->map(fn ($level): int => (int) $level)
            ->filter(fn (int $level): bool => $level >= 1 && $level <= 4)
            ->unique()
            ->sort()
            ->values();

        if ($selectedYearLevels->isEmpty()) {
            throw ValidationException::withMessages(['year_levels' => 'Select at least one year level.']);
        }

        $singleYearLevel = $selectedYearLevels->count() === 1;
        if ($singleYearLevel && blank($validated['number_of_sections'] ?? null)) {
            throw ValidationException::withMessages(['number_of_sections' => 'Enter the number of existing sections to use.']);
        }

        $availableSections = AcademicSection::query()
            ->forDepartment($department)
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
                'year_levels' => "No existing {$department} sections are available for the selected academic year and year level.",
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
        $subjects = $this->minorSubjects()
            ->with('instructors')
            ->where('course', $department)
            ->whereIn('year_level', $selectedYearLevels)
            ->where('semester', $validated['semester'])
            ->where('curriculum', $validated['curriculum'])
            ->get();
        $yearWithoutSubjects = $selectedYearLevels->first(
            fn ($yearLevel): bool => $subjects->where('year_level', $yearLevel)->isEmpty(),
        );

        if ($yearWithoutSubjects !== null) {
            throw ValidationException::withMessages([
                'year_levels' => "No {$validated['semester']} semester {$validated['curriculum']} Curriculum Minor subjects are available for {$department} Year {$yearWithoutSubjects}.",
            ]);
        }

        $rooms = Room::forDepartment($department)->orderBy('name')->get();
        $instructors = User::forDepartment('GEC')->where('role', 'instructor')->where('account_status', 'active')->get();

        $assignedInstructors = $subjects->flatMap->instructors->where('account_status', 'active');

        if ($subjects->isEmpty() || ($instructors->isEmpty() && $assignedInstructors->isEmpty())) {
            return $this->failureResponse('The schedule cannot be created because a required minor subject or active GEC instructor is missing.');
        }

        try {
            $created = $this->generator->generate($department, $sections, $subjects, $rooms, $instructors, $validated);
        } catch (ScheduleGenerationException $exception) {
            return $this->failureResponse(
                $exception->getMessage().' No schedules were changed.',
                $exception->guidance,
            );
        } catch (\RuntimeException $exception) {
            return $this->failureResponse($exception->getMessage().' No schedules were changed.');
        }

        $this->notifications->schedulesGenerated($sections, $validated);

        return redirect()
            ->route('gec.timetable.index', ['academic_year' => $validated['academic_year'], 'semester' => $validated['semester'], 'department' => $department])
            ->with('success', "{$created} minor-subject schedule entries generated successfully for {$department}.");
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
            str_contains($message, 'every allowed day and time') => 'Check the section timetable for occupied periods, assign another GEC instructor who still has available units, and verify the instructor is not already booked. If resources are limited, generate fewer sections at one time.',
            str_contains($message, 'No assigned instructor has enough workload capacity') => 'Every GEC instructor assigned to this subject has reached or would exceed their configured teaching-unit limit. Assign an instructor with remaining units or adjust the limit on the Instructor Units page.',
            str_contains($message, 'teaching units') => 'The generated load exceeds the instructor’s configured maximum. Reduce the assigned load or adjust the maximum on the Instructor Units page; instructors do not need to use all available units.',
            str_contains($message, 'required minor subject or active GEC instructor is missing') => 'Review the Minor Subjects, Subject Assignment, and Instructor List pages. Complete the missing information, then create the schedule again.',
            default => 'Review the selected department, academic period, sections, subject assignments, and instructor unit limits before trying again.',
        };
    }
}
