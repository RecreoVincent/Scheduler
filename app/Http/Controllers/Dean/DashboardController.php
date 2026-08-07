<?php

namespace App\Http\Controllers\Dean;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $instructorQuery = User::forDepartment($course)->where('role', 'instructor')->where('account_status', 'active');
        $studentQuery = User::forDepartment($course)->where('role', 'student');
        $statistics = [
            'instructors' => (clone $instructorQuery)->count(),
            'students' => (clone $studentQuery)->count(),
            'subjects' => Subject::forDepartment($course)->count(),
            'sections' => AcademicSection::forDepartment($course)->count(),
            'rooms' => Room::forDepartment($course)->count(),
        ];

        $analytics = [
            'instructors' => [
                'Full time' => (clone $instructorQuery)->where('employment_type', 'full_time')->count(),
                'Industry part time' => (clone $instructorQuery)->where('employment_type', 'industry_part_time')->count(),
                'Flexible part time' => (clone $instructorQuery)->whereIn('employment_type', ['flexible_part_time', 'part_time'])->count(),
                'Unspecified' => (clone $instructorQuery)->whereNull('employment_type')->count(),
            ],
            'students' => $this->yearLevelCounts(clone $studentQuery),
            'subjects' => $this->yearLevelCounts(Subject::forDepartment($course)),
            'sections' => $this->yearLevelCounts(AcademicSection::forDepartment($course)),
            'rooms' => Room::withCount('schedules')
                ->forDepartment($course)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (Room $room): array => [$room->name => (int) $room->getAttribute('schedules_count')])
                ->all(),
        ];

        $recentSchedules = ClassSchedule::with(['section', 'subject', 'room'])
            ->forDepartment($course)
            ->latest()
            ->take(6)
            ->get();

        return view('dean.dashboard', compact('course', 'statistics', 'analytics', 'recentSchedules'));
    }

    private function yearLevelCounts(Builder $query): array
    {
        $counts = [];

        for ($level = 1; $level <= 4; $level++) {
            $counts["Year {$level}"] = (clone $query)->where('year_level', $level)->count();
        }

        $counts['Unassigned'] = (clone $query)->whereNull('year_level')->count();

        return $counts;
    }
}
