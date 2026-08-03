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
        $instructorQuery = User::where('course', $course)->where('role', 'instructor')->where('account_status', 'active');
        $studentQuery = User::where('course', $course)->where('role', 'student');
        $statistics = [
            'instructors' => (clone $instructorQuery)->count(),
            'students' => (clone $studentQuery)->count(),
            'subjects' => Subject::where('course', $course)->count(),
            'sections' => AcademicSection::where('course', $course)->count(),
            'rooms' => Room::where('course', $course)->count(),
        ];

        $analytics = [
            'instructors' => [
                'Full time' => (clone $instructorQuery)->where('employment_type', 'full_time')->count(),
                'Industry part time' => (clone $instructorQuery)->where('employment_type', 'industry_part_time')->count(),
                'Flexible part time' => (clone $instructorQuery)->whereIn('employment_type', ['flexible_part_time', 'part_time'])->count(),
                'Unspecified' => (clone $instructorQuery)->whereNull('employment_type')->count(),
            ],
            'students' => $this->yearLevelCounts(clone $studentQuery),
            'subjects' => $this->yearLevelCounts(Subject::where('course', $course)),
            'sections' => $this->yearLevelCounts(AcademicSection::where('course', $course)),
            'rooms' => Room::withCount('schedules')
                ->where('course', $course)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (Room $room): array => [$room->name => (int) $room->getAttribute('schedules_count')])
                ->all(),
        ];

        $recentSchedules = ClassSchedule::with(['section', 'subject', 'room'])
            ->where('course', $course)
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
