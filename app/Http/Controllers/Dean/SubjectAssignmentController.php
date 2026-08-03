<?php

namespace App\Http\Controllers\Dean;

use App\Models\AcademicSection;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassScheduleGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectAssignmentController extends DeanController
{
    private const DEPARTMENTS = ['BSIT', 'BSBA', 'BSHM', 'BSED', 'BEED'];

    public function __construct(private readonly ClassScheduleGenerator $generator) {}

    public function index(Request $request): View
    {
        $course = $this->course($request);
        $query = Subject::with('instructors')->where('course', $course);

        foreach (['year_level', 'semester', 'curriculum'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(function ($subjectQuery) use ($search): void {
                $subjectQuery
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->input('assignment_status') === 'assigned') {
            $query->whereHas('instructors');
        } elseif ($request->input('assignment_status') === 'unassigned') {
            $query->whereDoesntHave('instructors');
        }

        $subjects = $query
            ->orderBy('year_level')
            ->orderBy('code')
            ->get();
        $assignmentCount = DB::table('subject_instructor')
            ->join('subjects', 'subjects.id', '=', 'subject_instructor.subject_id')
            ->where('subjects.course', $course)
            ->count();

        return view('dean.subject-assignments.index', array_merge(
            compact('course', 'subjects', 'assignmentCount'),
            $this->assignmentFormData($request, $course),
        ));
    }

    public function create(Request $request): View
    {
        return $this->index($request);
    }

    /** @return array<string, mixed> */
    private function assignmentFormData(Request $request, string $course): array
    {
        $subjectOptions = Subject::with('instructors')
            ->where('course', $course)
            ->orderBy('year_level')
            ->orderBy('code')
            ->get();
        $selectedSubject = null;

        if ($request->filled('subject_id')) {
            $selectedSubject = Subject::with('instructors')
                ->where('course', $course)
                ->findOrFail((int) $request->input('subject_id'));
        }

        $departments = self::DEPARTMENTS;
        $instructors = User::query()
            ->whereIn('course', $departments)
            ->where('role', 'instructor')
            ->where('account_status', 'active')
            ->orderBy('course')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $instructorIds = $instructors->modelKeys();
        $activeAcademicYear = AcademicSection::query()
            ->where('course', $course)
            ->max('academic_year');
        $scheduledInstructorLoads = $activeAcademicYear
            ? DB::table('class_schedules')
                ->join('subjects', 'subjects.id', '=', 'class_schedules.subject_id')
                ->whereNull('class_schedules.deleted_at')
                ->where('class_schedules.academic_year', $activeAcademicYear)
                ->whereIn('class_schedules.instructor_id', $instructorIds)
                ->selectRaw('class_schedules.instructor_id, class_schedules.semester, SUM(subjects.units) as units')
                ->groupBy('class_schedules.instructor_id', 'class_schedules.semester')
                ->get()
                ->groupBy('instructor_id')
                ->map(fn ($loads) => $loads->mapWithKeys(
                    fn ($load): array => [(string) $load->semester => (float) $load->units],
                )->all())
                ->all()
            : [];
        $assignedInstructorLoads = DB::table('subject_instructor')
            ->join('subjects', 'subjects.id', '=', 'subject_instructor.subject_id')
            ->whereIn('subject_instructor.instructor_id', $instructorIds)
            ->selectRaw('subject_instructor.instructor_id, subjects.semester, SUM(subjects.units) as units')
            ->groupBy('subject_instructor.instructor_id', 'subjects.semester')
            ->get()
            ->groupBy('instructor_id')
            ->map(fn ($loads) => $loads->mapWithKeys(
                fn ($load): array => [(string) $load->semester => (float) $load->units],
            )->all())
            ->all();
        $instructorLimits = $instructors->mapWithKeys(
            fn (User $instructor): array => [
                (string) $instructor->id => (float) $this->generator->workloadRange($instructor)[1],
            ],
        )->all();
        $semesters = collect(['1st', '2nd', 'Summer']);
        $selectedSemester = old(
            'semester',
            $selectedSubject?->semester ?? $request->string('semester')->toString(),
        );
        $selectedYearLevel = (string) old(
            'year_level',
            $selectedSubject?->year_level ?? $request->string('year_level')->toString(),
        );
        $selectedDepartment = old(
            'instructor_department',
            $selectedSubject?->instructors->first()?->course ?? $course,
        );
        $subjectAssignments = $subjectOptions->mapWithKeys(fn (Subject $subject): array => [
            (string) $subject->id => [
                'department' => $subject->instructors->first()?->course ?? $course,
                'instructor_ids' => $subject->instructors
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all(),
                'units' => (float) $subject->units,
                'semester' => $subject->semester,
                'year_level' => (int) $subject->year_level,
            ],
        ]);

        return compact(
            'subjectOptions',
            'selectedSubject',
            'semesters',
            'selectedSemester',
            'selectedYearLevel',
            'departments',
            'instructors',
            'selectedDepartment',
            'subjectAssignments',
            'scheduledInstructorLoads',
            'assignedInstructorLoads',
            'instructorLimits',
            'activeAcademicYear',
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
            'year_level' => ['nullable', 'integer', 'between:1,4'],
            'subject_id' => ['required', 'integer'],
            'instructor_department' => ['required', Rule::in(self::DEPARTMENTS)],
            'instructor_ids' => ['required', 'array', 'min:1', 'max:4'],
            'instructor_ids.*' => ['nullable', 'integer'],
            'return_search' => ['nullable', 'string', 'max:100'],
            'return_year_level' => ['nullable', 'integer', 'between:1,4'],
            'return_semester' => ['nullable', Rule::in(['1st', '2nd', 'Summer'])],
            'return_curriculum' => ['nullable', Rule::in(['New', 'Old'])],
            'return_assignment_status' => ['nullable', Rule::in(['assigned', 'unassigned'])],
        ]);
        $priorityInstructorIds = collect($validated['instructor_ids'])
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($priorityInstructorIds->isEmpty()) {
            throw ValidationException::withMessages([
                'instructor_ids' => 'Select at least one Priority 1 instructor.',
            ]);
        }


        if ($priorityInstructorIds->unique()->count() !== $priorityInstructorIds->count()) {
            throw ValidationException::withMessages([
                'instructor_ids' => 'Each instructor can only be selected once in the priority list.',
            ]);
        }
        $subjectQuery = Subject::query()
            ->where('course', $this->course($request))
            ->where('semester', $validated['semester']);

        if (filled($validated['year_level'] ?? null)) {
            $subjectQuery->where('year_level', $validated['year_level']);
        }

        $subject = $subjectQuery->findOrFail($validated['subject_id']);

        $instructorsById = User::query()
            ->whereIn('id', $priorityInstructorIds)
            ->where('course', $validated['instructor_department'])
            ->where('role', 'instructor')
            ->where('account_status', 'active')
            ->get()
            ->keyBy('id');
        $instructors = $priorityInstructorIds
            ->map(fn ($id) => $instructorsById->get((int) $id))
            ->filter()
            ->values();

        abort_unless($instructors->count() === $priorityInstructorIds->count(), 422);

        $alreadyAssignedInstructorIds = $subject->instructors()
            ->pluck('users.id')
            ->map(fn ($id): int => (int) $id);
        $activeAcademicYear = AcademicSection::query()
            ->where('course', $this->course($request))
            ->max('academic_year');
        $scheduledLoads = $activeAcademicYear
            ? DB::table('class_schedules')
                ->join('subjects', 'subjects.id', '=', 'class_schedules.subject_id')
                ->whereNull('class_schedules.deleted_at')
                ->whereIn('class_schedules.instructor_id', $instructors->pluck('id'))
                ->where('class_schedules.academic_year', $activeAcademicYear)
                ->where('class_schedules.semester', $subject->semester)
                ->selectRaw('class_schedules.instructor_id, SUM(subjects.units) as units')
                ->groupBy('class_schedules.instructor_id')
                ->pluck('units', 'instructor_id')
            : collect();
        $assignedLoads = DB::table('subject_instructor')
            ->join('subjects', 'subjects.id', '=', 'subject_instructor.subject_id')
            ->whereIn('subject_instructor.instructor_id', $instructors->pluck('id'))
            ->where('subjects.semester', $subject->semester)
            ->selectRaw('subject_instructor.instructor_id, SUM(subjects.units) as units')
            ->groupBy('subject_instructor.instructor_id')
            ->pluck('units', 'subject_instructor.instructor_id');

        $overCapacityInstructor = $instructors->first(function (User $instructor) use (
            $subject,
            $alreadyAssignedInstructorIds,
            $scheduledLoads,
            $assignedLoads,
        ): bool {
            if ($alreadyAssignedInstructorIds->contains($instructor->id)) {
                return false;
            }

            $currentUnits = max(
                (float) ($scheduledLoads[$instructor->id] ?? 0),
                (float) ($assignedLoads[$instructor->id] ?? 0),
            );
            $maximumUnits = (float) $this->generator->workloadRange($instructor)[1];

            return $currentUnits + (float) $subject->units > $maximumUnits;
        });

        if ($overCapacityInstructor) {
            $currentUnits = max(
                (float) ($scheduledLoads[$overCapacityInstructor->id] ?? 0),
                (float) ($assignedLoads[$overCapacityInstructor->id] ?? 0),
            );
            $maximumUnits = (float) $this->generator->workloadRange($overCapacityInstructor)[1];

            throw ValidationException::withMessages([
                'instructor_ids' => "{$overCapacityInstructor->name} already has {$currentUnits} assigned units and cannot accept {$subject->units} more. The maximum is {$maximumUnits} units.",
            ]);
        }

        $priorities = $instructors
            ->values()
            ->mapWithKeys(fn (User $instructor, int $index): array => [
                $instructor->id => ['priority' => $index + 1],
            ])
            ->all();
        $subject->instructors()->sync($priorities);
        $instructorCount = $instructors->count();
        $message = "{$instructorCount} ".str('instructor')->plural($instructorCount)." assigned to {$subject->code} successfully.";

        $returnFilters = array_filter([
            'search' => $validated['return_search'] ?? null,
            'year_level' => $validated['return_year_level'] ?? null,
            'semester' => $validated['return_semester'] ?? null,
            'curriculum' => $validated['return_curriculum'] ?? null,
            'assignment_status' => $validated['return_assignment_status'] ?? null,
        ], fn ($value): bool => filled($value));

        return redirect()
            ->route('dean.subject-assignments.index', $returnFilters)
            ->with('success', $message);
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $course = $this->course($request);
        $subjectIds = Subject::query()
            ->where('course', $course)
            ->pluck('id');

        $removedCount = DB::table('subject_instructor')
            ->whereIn('subject_id', $subjectIds)
            ->delete();

        if ($removedCount === 0) {
            return back()->with('error', "There are no {$course} subject assignments to remove.");
        }

        return redirect()
            ->route('dean.subject-assignments.index')
            ->with(
                'success',
                "All {$course} subject assignments were removed successfully ({$removedCount} ".str('assignment')->plural($removedCount).').',
            );
    }
}
