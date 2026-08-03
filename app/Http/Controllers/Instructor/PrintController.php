<?php

namespace App\Http\Controllers\Instructor;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintController extends InstructorController
{
    public function workload(Request $request): View
    {
        $schedules = $this->schedules($request)
            ->with(['section', 'subject', 'room'])
            ->orderByRaw(ClassSchedule::dayOrderSql())
            ->orderBy('start_time')
            ->get();
        $weeklyHours = $this->formattedHours($this->weeklyMinutes($this->schedules($request)));
        $instructor = $this->instructor($request);

        return view('instructor.print.workload', compact('instructor', 'schedules', 'weeklyHours'));
    }
}
