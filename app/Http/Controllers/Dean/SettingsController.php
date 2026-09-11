<?php

namespace App\Http\Controllers\Dean;

use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends DeanController
{
    public function updateSemesters(Request $request): RedirectResponse
    {
        $department = Department::where('code', $this->course($request))->firstOrFail();

        $department->update([
            'semester_first_enabled' => $request->boolean('semester_first_enabled'),
            'semester_second_enabled' => $request->boolean('semester_second_enabled'),
            'semester_summer_enabled' => $request->boolean('semester_summer_enabled'),
        ]);

        return back()->with('success', 'Semester availability updated successfully.');
    }
}
