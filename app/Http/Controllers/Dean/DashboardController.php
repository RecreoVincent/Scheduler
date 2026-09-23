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
        $semester = $this->enabledSemesters($request)[0] ?? '1st';
        $semesterScheduleQuery = ClassSchedule::query()
            ->forDepartment($course)
            ->where('semester', $semester);
        $instructorQuery = User::query()
            ->forDepartment($course)
            ->where('role', 'instructor');
        $studentQuery = User::query()
            ->forDepartment($course)
            ->where('role', 'student');
        $subjectQuery = Subject::query()->forDepartment($course)->where('semester', $semester);
        $sectionQuery = AcademicSection::query()
            ->forDepartment($course);
        $roomQuery = Room::query()
            ->forDepartment($course);
        $statistics = [
            'instructors' => (clone $instructorQuery)->count(),
            'students' => (clone $studentQuery)->count(),
            'subjects' => (clone $subjectQuery)->count(),
            'sections' => (clone $sectionQuery)->count(),
            'rooms' => (clone $roomQuery)->count(),
        ];

        $analytics = [
            'instructors' => [
                'Full time' => (clone $instructorQuery)->where('employment_type', 'full_time')->count(),
                'Industry part time' => (clone $instructorQuery)->where('employment_type', 'industry_part_time')->count(),
                'Flexible part time' => (clone $instructorQuery)->whereIn('employment_type', ['flexible_part_time', 'part_time'])->count(),
                'Unspecified' => (clone $instructorQuery)->whereNull('employment_type')->count(),
            ],
            'students' => $this->yearLevelCounts(clone $studentQuery),
            'subjects' => $this->yearLevelCounts(clone $subjectQuery),
            'sections' => $this->yearLevelCounts(clone $sectionQuery),
            'rooms' => (clone $roomQuery)
                ->selectRaw('room_type, COUNT(*) as rooms_count')
                ->groupBy('room_type')
                ->orderBy('room_type')
                ->get()
                ->mapWithKeys(fn (Room $room): array => [$room->room_type => (int) $room->getAttribute('rooms_count')])
                ->all(),
        ];

        $recentSchedules = (clone $semesterScheduleQuery)
            ->with(['section', 'subject', 'room'])
            ->latest()
            ->take(6)
            ->get();

        return view('dean.dashboard', compact('course', 'semester', 'statistics', 'analytics', 'recentSchedules'));
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
