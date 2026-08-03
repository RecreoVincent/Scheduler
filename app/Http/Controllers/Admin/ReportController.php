<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function instructorWorkload()
    {
        $instructors = User::where('role', 'instructor')
            ->orderBy('course')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view(
            'admin.reports.instructor-workload',
            compact('instructors')
        );
    }

    public function studyLoad()
    {
        $students = User::where('role', 'student')
            ->orderBy('course')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view(
            'admin.reports.study-load',
            compact('students')
        );
    }

    public function summary()
    {
        $summary = [
            'deans' => User::where('role', 'dean')->count(),
            'instructors' => User::where('role', 'instructor')->count(),
            'students' => User::where('role', 'student')->count(),
        ];

        return view(
            'admin.reports.summary',
            compact('summary')
        );
    }
}
