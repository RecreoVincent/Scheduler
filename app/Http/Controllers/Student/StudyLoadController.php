<?php

namespace App\Http\Controllers\Student;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudyLoadController extends StudentController
{
    public function index(Request $request): View
    {
        $query = $this->schedules($request)->with(['section', 'subject', 'instructor', 'room']);

        $schedules = $query->orderByRaw(ClassSchedule::dayOrderSql())->orderBy('start_time')->get();
        $student = $this->student($request)->load('academicSection');

        return view('student.study-load.index', compact('student', 'schedules'));
    }
}
