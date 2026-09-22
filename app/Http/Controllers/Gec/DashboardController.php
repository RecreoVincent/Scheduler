<?php

namespace App\Http\Controllers\Gec;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends GecController
{
    public function index(Request $request): View
    {
        $semester = $this->enabledSemesters($request)[0] ?? '1st';
        $instructorQuery = User::query()
            ->forDepartment('GEC')
            ->where('role', 'instructor')
            ->where('account_status', 'active')
            ->whereHas('classSchedules', fn ($query) => $query
                ->where('semester', $semester)
                ->whereHas('subject', fn ($subjectQuery) => $subjectQuery->where('classification', 'Minor')));
        $subjectsQuery = $this->minorSubjects()->where('semester', $semester);
        $schedulesQuery = $this->minorSchedules()->where('semester', $semester);

        $statistics = [
            'instructors' => (clone $instructorQuery)->count(),
            'subjects' => (clone $subjectsQuery)->count(),
            'assignments' => (clone $subjectsQuery)->whereHas('instructors')->count(),
            'schedules' => (clone $schedulesQuery)->count(),
        ];

        $analytics = [
            'instructors' => [
                'Full time' => (clone $instructorQuery)->where('employment_type', 'full_time')->count(),
                'Industry part time' => (clone $instructorQuery)->where('employment_type', 'industry_part_time')->count(),
                'Flexible part time' => (clone $instructorQuery)->whereIn('employment_type', ['flexible_part_time', 'part_time'])->count(),
                'Unspecified' => (clone $instructorQuery)->whereNull('employment_type')->count(),
            ],
            'subjects' => $this->departmentCounts(fn (string $department) => (clone $subjectsQuery)->where('course', $department)->count()),
            'assignments' => [
                'Assigned' => (clone $subjectsQuery)->whereHas('instructors')->count(),
                'Unassigned' => (clone $subjectsQuery)->whereDoesntHave('instructors')->count(),
            ],
            'schedules' => $this->departmentCounts(fn (string $department) => (clone $schedulesQuery)->where('course', $department)->count()),
        ];

        $recentSchedules = (clone $schedulesQuery)
            ->with(['section', 'subject', 'room'])
            ->latest()
            ->take(6)
            ->get();

        return view('gec.dashboard', compact('semester', 'statistics', 'analytics', 'recentSchedules'));
    }

    /** @return array<string, int> */
    private function departmentCounts(callable $counter): array
    {
        $counts = [];

        foreach (self::REAL_DEPARTMENTS as $department) {
            $counts[$department] = $counter($department);
        }

        return $counts;
    }
}
