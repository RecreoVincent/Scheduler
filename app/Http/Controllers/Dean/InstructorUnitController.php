<?php

namespace App\Http\Controllers\Dean;

use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Subject;
use App\Models\User;
use App\Services\FacultyLoadWeeklyHours;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstructorUnitController extends DeanController
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'academic_year' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', Rule::in(['1st', '2nd', 'Summer'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $course = $this->course($request);
        $department = Department::where('code', $course)->first();
        $enabledSemesters = $department?->enabledSemesterCodes() ?? ['1st', '2nd', 'Summer'];
        $academicYears = ClassSchedule::query()
            ->forDepartment($course)
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');
        $academicYear = $validated['academic_year'] ?? $academicYears->first();
        $semester = $validated['semester'] ?? ($enabledSemesters[0] ?? '1st');

        $activeInstructorQuery = User::query()
            ->with('department')
            ->where('role', 'instructor')
            ->forDepartment($course)
            ->where('account_status', 'active');

        $capacityInstructors = (clone $activeInstructorQuery)->get();
        $query = clone $activeInstructorQuery;

        if (filled($validated['search'] ?? null)) {
            $search = $validated['search'];
            $query->where(fn ($builder) => $builder
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('middle_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $instructors = $query
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $scheduledUnits = collect();
        if ($academicYear && $instructors->isNotEmpty()) {
            $scheduledUnits = DB::table('class_schedules')
                ->join('subjects', 'subjects.id', '=', 'class_schedules.subject_id')
                ->whereNull('class_schedules.deleted_at')
                ->where('class_schedules.course', $course)
                ->where('class_schedules.academic_year', $academicYear)
                ->where('class_schedules.semester', $semester)
                ->whereIn('class_schedules.instructor_id', $instructors->getCollection()->modelKeys())
                ->selectRaw('class_schedules.instructor_id, SUM('.FacultyLoadWeeklyHours::sqlExpression().') as units')
                ->groupBy('class_schedules.instructor_id')
                ->pluck('units', 'instructor_id');
        }

        $defaultUnitLimits = [
            'full_time' => $department?->default_unit_limit_full_time ?? User::DEFAULT_UNIT_LIMITS['full_time'],
            'industry_part_time' => $department?->default_unit_limit_industry_part_time ?? User::DEFAULT_UNIT_LIMITS['industry_part_time'],
            'flexible_part_time' => $department?->default_unit_limit_flexible_part_time ?? User::DEFAULT_UNIT_LIMITS['flexible_part_time'],
        ];

        $totalSubjectUnits = (float) (Subject::query()
            ->forDepartment($course)
            ->where('managed_by_gec', false)
            ->where('classification', 'Major')
            ->where('semester', $semester)
            ->selectRaw('SUM('.FacultyLoadWeeklyHours::sqlExpression().') as hours')
            ->first()?->hours ?? 0);
        $totalInstructorCapacity = (float) $capacityInstructors
            ->sum(fn (User $instructor): int => $instructor->effectiveTeachingUnitLimit());
        $unitShortfall = max(0, $totalSubjectUnits - $totalInstructorCapacity);
        $fullTimeCapacity = (int) $defaultUnitLimits['full_time'];
        $recommendedHires = $unitShortfall > 0 && $fullTimeCapacity > 0
            ? (int) ceil($unitShortfall / $fullTimeCapacity)
            : null;
        $capacitySummary = compact(
            'totalSubjectUnits',
            'totalInstructorCapacity',
            'unitShortfall',
            'fullTimeCapacity',
            'recommendedHires',
        );

        return view('dean.instructor-units.index', compact(
            'course',
            'instructors',
            'scheduledUnits',
            'academicYears',
            'enabledSemesters',
            'academicYear',
            'semester',
            'defaultUnitLimits',
            'capacitySummary',
        ));
    }

    public function updateDefaults(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_unit_limit_full_time' => ['required', 'integer', 'min:0', 'max:60'],
            'default_unit_limit_industry_part_time' => ['required', 'integer', 'min:0', 'max:60'],
            'default_unit_limit_flexible_part_time' => ['required', 'integer', 'min:0', 'max:60'],
        ]);

        $department = Department::where('code', $this->course($request))->firstOrFail();
        $department->update($validated);

        return back()->with('success', 'Default workload-hour limits updated successfully.');
    }

    public function update(Request $request, User $instructor): RedirectResponse
    {
        abort_unless(
            $instructor->role === 'instructor'
            && $instructor->account_status === 'active'
            && strtoupper((string) $instructor->course) === $this->course($request),
            404,
        );

        $validated = $request->validate([
            'teaching_unit_limit' => ['required', 'integer', 'min:0', 'max:60'],
            'unit_limit_note' => ['nullable', 'string', 'max:500'],
        ]);
        $previousLimit = $instructor->effectiveTeachingUnitLimit();

        $instructor->update([
            'teaching_unit_limit' => $validated['teaching_unit_limit'],
            'unit_limit_note' => $validated['unit_limit_note'] ?? null,
            'unit_limit_updated_at' => now(),
        ]);

        return back()->with(
            'success',
            "{$instructor->name}'s workload-hour limit was changed from {$previousLimit} to {$validated['teaching_unit_limit']} hours.",
        );
    }

    public function destroy(Request $request, User $instructor): RedirectResponse
    {
        abort_unless(
            $instructor->role === 'instructor'
            && $instructor->account_status === 'active'
            && strtoupper((string) $instructor->course) === $this->course($request),
            404,
        );
        abort_if($instructor->teaching_unit_limit === null, 422, 'This instructor already uses the default workload-hour limit.');

        $instructor->update([
            'teaching_unit_limit' => null,
            'unit_limit_note' => null,
            'unit_limit_updated_at' => null,
        ]);

        return back()->with('success', "{$instructor->name}'s workload-hour limit was reset to the default.");
    }
}
