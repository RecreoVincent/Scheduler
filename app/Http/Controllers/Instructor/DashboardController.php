<?php

namespace App\Http\Controllers\Instructor;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends InstructorController
{
    public function index(Request $request): View
    {
        $query = $this->schedules($request);
        $statistics = [
            'sections' => (clone $query)->distinct()->count('section_id'),
            'subjects' => (clone $query)->distinct()->count('subject_id'),
            'hours' => $this->formattedHours($this->weeklyMinutes(clone $query)),
        ];

        $schedules = (clone $query)
            ->with(['section', 'subject', 'room'])
            ->orderByRaw(ClassSchedule::dayOrderSql())
            ->orderBy('start_time')
            ->get();

        $today = now()->format('l');
        $todaySchedules = $schedules
            ->filter(fn (ClassSchedule $schedule): bool => in_array($today, ClassSchedule::daysForPattern($schedule->day), true))
            ->sortBy('start_time')
            ->values();

        return view('instructor.dashboard', compact('statistics', 'schedules', 'todaySchedules', 'today'));
    }
}
