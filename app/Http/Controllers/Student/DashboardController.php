<?php

namespace App\Http\Controllers\Student;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends StudentController
{
    public function index(Request $request): View
    {
        $query = $this->schedules($request);
        $schedules = (clone $query)->with(['section', 'subject', 'instructor', 'room'])->get();
        $student = $this->student($request)->load('academicSection');
        $subjects = $schedules->pluck('subject')->filter()->unique('id');
        $statistics = [
            'subjects' => $subjects->count(),
            'units' => number_format((float) $subjects->sum('units'), 0),
            'hours' => $this->formattedHours($this->weeklyMinutes(clone $query)),
        ];
        $today = now()->format('l');
        $todaySchedules = $schedules
            ->filter(fn (ClassSchedule $schedule): bool => in_array($today, ClassSchedule::daysForPattern($schedule->day), true))
            ->sortBy('start_time')
            ->values();

        return view('student.dashboard', compact('student', 'statistics', 'today', 'todaySchedules'));
    }
}
