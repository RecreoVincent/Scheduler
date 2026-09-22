<?php

namespace App\Http\Controllers\Instructor;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkloadController extends InstructorController
{
    public function index(Request $request): View
    {
        $query = $this->schedules($request)->with(['section', 'subject', 'room']);

        $schedules = $query->orderByRaw(ClassSchedule::dayOrderSql())->orderBy('start_time')->paginate($this->perPage($request))->withQueryString();

        return view('instructor.workload.index', compact('schedules'));
    }
}
