<?php

namespace App\Http\Controllers\Dean;

use App\Models\ClassSchedule;
use App\Models\User;
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
        $academicYears = ClassSchedule::query()
            ->where('course', $course)
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');
        $academicYear = $validated['academic_year'] ?? $academicYears->first();
        $semester = $validated['semester'] ?? '1st';

        $query = User::query()
            ->where('role', 'instructor')
            ->where('course', $course)
            ->where('account_status', 'active');

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
            ->paginate(12)
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
                ->selectRaw('class_schedules.instructor_id, SUM(subjects.units) as units')
                ->groupBy('class_schedules.instructor_id')
                ->pluck('units', 'instructor_id');
        }

        return view('dean.instructor-units.index', compact(
            'course',
            'instructors',
            'scheduledUnits',
            'academicYears',
            'academicYear',
            'semester',
        ));
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
            "{$instructor->name}'s teaching-unit limit was changed from {$previousLimit} to {$validated['teaching_unit_limit']} units.",
        );
    }
}
