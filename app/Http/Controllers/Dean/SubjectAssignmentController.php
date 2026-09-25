<?php

namespace App\Http\Controllers\Dean;

use App\Models\AcademicSection;
use App\Models\CrossDepartmentInstructorRequest;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassScheduleGenerator;
use App\Services\CrossDepartmentInstructorRequestNotifier;
use App\Services\FacultyLoadWeeklyHours;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectAssignmentController extends DeanController
{
    /** @var array<int, string> */
    private const DEPARTMENTS = ['BSIT', 'BSBA', 'BSHM', 'BSED', 'BEED'];

    public function __construct(
        private readonly ClassScheduleGenerator $generator,
        private readonly CrossDepartmentInstructorRequestNotifier $requestNotifier,
    ) {}

    public function index(Request $request): View
    {
        $course = $this->course($request);
        $enabledSemesters = $this->enabledSemesters($request);
        $query = Subject::with('instructors')->forDepartment($course)->where('managed_by_gec', false)->whereIn('semester', $enabledSemesters);

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
            ->where('subjects.managed_by_gec', false)
            ->count();

        return view('dean.subject-assignments.index', array_merge(
            compact('course', 'subjects', 'assignmentCount', 'enabledSemesters'),
            $this->assignmentFormData($request, $course, $enabledSemesters),
        ));
    }

    public function create(Request $request): View
    {
        return $this->index($request);
    }

    /** @param array<int, string> $enabledSemesters
     *  @return array<string, mixed> */
    private function assignmentFormData(Request $request, string $course, array $enabledSemesters): array
    {
        $subjectOptions = Subject::with('instructors')
            ->forDepartment($course)
            ->where('managed_by_gec', false)
            ->whereIn('semester', $enabledSemesters)
            ->orderBy('year_level')
            ->orderBy('code')
            ->get();
        $selectedSubject = null;

        if ($request->filled('subject_id')) {
            $selectedSubject = Subject::with('instructors')
                ->forDepartment($course)
                ->where('managed_by_gec', false)
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
            ->forDepartment($course)
            ->max('academic_year');
        $scheduledInstructorLoads = $activeAcademicYear
            ? DB::table('class_schedules')
                ->join('subjects', 'subjects.id', '=', 'class_schedules.subject_id')
                ->whereNull('class_schedules.deleted_at')
                ->where('class_schedules.academic_year', $activeAcademicYear)
                ->whereIn('class_schedules.instructor_id', $instructorIds)
                ->selectRaw('class_schedules.instructor_id, class_schedules.semester, SUM('.FacultyLoadWeeklyHours::sqlExpression().') as hours')
                ->groupBy('class_schedules.instructor_id', 'class_schedules.semester')
                ->get()
                ->groupBy('instructor_id')
                ->map(fn ($loads) => $loads->mapWithKeys(
                    fn ($load): array => [(string) $load->semester => (float) $load->hours],
                )->all())
                ->all()
            : [];
        $assignedInstructorLoads = DB::table('subject_instructor')
            ->join('subjects', 'subjects.id', '=', 'subject_instructor.subject_id')
            ->whereIn('subject_instructor.instructor_id', $instructorIds)
            ->selectRaw('subject_instructor.instructor_id, subjects.semester, SUM('.FacultyLoadWeeklyHours::sqlExpression().') as hours')
            ->groupBy('subject_instructor.instructor_id', 'subjects.semester')
            ->get()
            ->groupBy('instructor_id')
            ->map(fn ($loads) => $loads->mapWithKeys(
                fn ($load): array => [(string) $load->semester => (float) $load->hours],
            )->all())
            ->all();
        $instructorLimits = $instructors->mapWithKeys(
            fn (User $instructor): array => [
                (string) $instructor->id => $this->generator->workloadHourLimit($instructor),
            ],
        )->all();
        $semesters = collect($enabledSemesters);
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
                'instructor_ids' => $subject->instructors
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all(),
                'workload_hours' => $this->generator->workloadHoursForSubject($subject),
                'semester' => $subject->semester,
                'year_level' => (int) $subject->year_level,
                'department' => $subject->instructors->first()?->course ?? $course,
            ],
        ]);

        return compact(
            'subjectOptions',
            'selectedSubject',
            'semesters',
            'selectedSemester',
            'selectedYearLevel',
            'departments',
            'selectedDepartment',
            'instructors',
            'subjectAssignments',
            'scheduledInstructorLoads',
            'assignedInstructorLoads',
            'instructorLimits',
            'activeAcademicYear',
        );
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->filled('instructor_department')) {
            $request->merge(['instructor_department' => $this->course($request)]);
        }

        $validated = $request->validate([
            'semester' => ['required', Rule::in($this->enabledSemesters($request))],
            'year_level' => ['nullable', 'integer', 'between:1,4'],
            'subject_id' => ['required', 'integer'],
            'instructor_department' => ['required', Rule::in(self::DEPARTMENTS)],
            'instructor_ids' => ['nullable', 'array', 'max:6'],
            'instructor_ids.*' => ['nullable', 'integer'],
            'return_search' => ['nullable', 'string', 'max:100'],
            'return_year_level' => ['nullable', 'integer', 'between:1,4'],
            'return_semester' => ['nullable', Rule::in(['1st', '2nd', 'Summer'])],
            'return_curriculum' => ['nullable', Rule::in(['New', 'Old'])],
            'return_assignment_status' => ['nullable', Rule::in(['assigned', 'unassigned'])],
        ]);
        $course = $this->course($request);
        $subjectQuery = Subject::query()
            ->forDepartment($course)
            ->where('managed_by_gec', false)
            ->where('semester', $validated['semester']);

        if (filled($validated['year_level'] ?? null)) {
            $subjectQuery->where('year_level', $validated['year_level']);
        }

        $subject = $subjectQuery->findOrFail($validated['subject_id']);

        if ($validated['instructor_department'] !== $course) {
            $instructorRequest = CrossDepartmentInstructorRequest::query()
                ->where('subject_id', $subject->id)
                ->where('requesting_department', $course)
                ->where('requested_department', $validated['instructor_department'])
                ->where('status', 'pending')
                ->first();

            if ($instructorRequest === null) {
                $instructorRequest = CrossDepartmentInstructorRequest::create([
                    'subject_id' => $subject->id,
                    'requesting_department' => $course,
                    'requested_department' => $validated['instructor_department'],
                    'requested_by' => $request->user()->id,
                ]);
                $recipientCount = $this->requestNotifier->notifyReceivingDeans($instructorRequest);
                $message = $recipientCount > 0
                    ? "A {$validated['instructor_department']} instructor was requested for {$subject->code}. The {$validated['instructor_department']} Dean was notified."
                    : "A {$validated['instructor_department']} instructor was requested for {$subject->code}, but no active {$validated['instructor_department']} Dean account exists yet. The request is queued and will be delivered when that Dean account is created or activated.";
            } else {
                $message = "A {$validated['instructor_department']} instructor request for {$subject->code} is already pending.";
            }

            return redirect()
                ->route('dean.subject-assignments.index', $this->returnFilters($validated))
                ->with('success', $message);
        }

        $priorityInstructorIds = collect($validated['instructor_ids'] ?? [])
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

        $instructorsById = User::query()
            ->whereIn('id', $priorityInstructorIds)
            ->forDepartment($course)
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
            ->forDepartment($course)
            ->max('academic_year');
        $scheduledLoads = $activeAcademicYear
            ? DB::table('class_schedules')
                ->join('subjects', 'subjects.id', '=', 'class_schedules.subject_id')
                ->whereNull('class_schedules.deleted_at')
                ->whereIn('class_schedules.instructor_id', $instructors->pluck('id'))
                ->where('class_schedules.academic_year', $activeAcademicYear)
                ->where('class_schedules.semester', $subject->semester)
                ->selectRaw('class_schedules.instructor_id, SUM('.FacultyLoadWeeklyHours::sqlExpression().') as hours')
                ->groupBy('class_schedules.instructor_id')
                ->pluck('hours', 'instructor_id')
            : collect();
        $assignedLoads = DB::table('subject_instructor')
            ->join('subjects', 'subjects.id', '=', 'subject_instructor.subject_id')
            ->whereIn('subject_instructor.instructor_id', $instructors->pluck('id'))
            ->where('subjects.semester', $subject->semester)
            ->selectRaw('subject_instructor.instructor_id, SUM('.FacultyLoadWeeklyHours::sqlExpression().') as hours')
            ->groupBy('subject_instructor.instructor_id')
            ->pluck('hours', 'subject_instructor.instructor_id');

        $overCapacityInstructor = $instructors->first(function (User $instructor) use (
            $subject,
            $alreadyAssignedInstructorIds,
            $scheduledLoads,
            $assignedLoads,
        ): bool {
            if ($alreadyAssignedInstructorIds->contains($instructor->id)) {
                return false;
            }

            $currentHours = max(
                (float) ($scheduledLoads[$instructor->id] ?? 0),
                (float) ($assignedLoads[$instructor->id] ?? 0),
            );
            $maximumHours = $this->generator->workloadHourLimit($instructor);

            return $currentHours + $this->generator->workloadHoursForSubject($subject) > $maximumHours;
        });

        if ($overCapacityInstructor) {
            $currentHours = max(
                (float) ($scheduledLoads[$overCapacityInstructor->id] ?? 0),
                (float) ($assignedLoads[$overCapacityInstructor->id] ?? 0),
            );
            $maximumHours = $this->generator->workloadHourLimit($overCapacityInstructor);
            $subjectWorkloadHours = $this->generator->workloadHoursForSubject($subject);

            throw ValidationException::withMessages([
                'instructor_ids' => "{$overCapacityInstructor->name} already has {$currentHours} workload hours and cannot accept {$subjectWorkloadHours} more. The maximum is {$maximumHours} hours.",
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

        return redirect()
            ->route('dean.subject-assignments.index', $this->returnFilters($validated))
            ->with('success', $message);
    }

    /** @param array<string, mixed> $validated
     *  @return array<string, mixed> */
    private function returnFilters(array $validated): array
    {
        return array_filter([
            'search' => $validated['return_search'] ?? null,
            'year_level' => $validated['return_year_level'] ?? null,
            'semester' => $validated['return_semester'] ?? null,
            'curriculum' => $validated['return_curriculum'] ?? null,
            'assignment_status' => $validated['return_assignment_status'] ?? null,
        ], fn ($value): bool => filled($value));
    }

    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureCourse($request, $subject);
        abort_if($subject->managed_by_gec, 404);

        $removedCount = $subject->instructors()->count();

        if ($removedCount === 0) {
            return back()->with('error', "{$subject->code} has no instructor assignments to remove.");
        }

        $subject->instructors()->sync([]);

        return back()->with(
            'success',
            "Removed {$removedCount} instructor ".str('assignment')->plural($removedCount)." from {$subject->code}.",
        );
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $course = $this->course($request);
        $subjectIds = Subject::query()
            ->forDepartment($course)
            ->where('managed_by_gec', false)
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
