<?php

namespace App\Http\Controllers\Student;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintController extends StudentController
{
    public function studyLoad(Request $request): View
    {
        $schedules = $this->schedules($request)->with(['subject', 'instructor', 'room'])->orderByRaw(ClassSchedule::dayOrderSql())->orderBy('start_time')->get();
        $subjects = $schedules->pluck('subject')->filter()->unique('id');
        $totalUnits = number_format((float) $subjects->sum('units'), 1);
        $student = $this->student($request)->load('academicSection');

        return view('student.print.study-load', compact('student', 'schedules', 'subjects', 'totalUnits'));
    }
}
