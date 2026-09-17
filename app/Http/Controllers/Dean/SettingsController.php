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
        $activeSemester = $request->input('active_semester');

        if ($activeSemester !== null) {
            if (! in_array($activeSemester, ['first', 'second', 'summer'], true)) {
                return back()->withErrors([
                    'semester_availability' => 'Choose a valid active semester before saving.',
                ]);
            }

            $settings = [
                'semester_first_enabled' => $activeSemester === 'first',
                'semester_second_enabled' => $activeSemester === 'second',
                'semester_summer_enabled' => $activeSemester === 'summer',
            ];
        } else {
            // Retain support for existing checkbox submissions.
            $settings = [
                'semester_first_enabled' => $request->boolean('semester_first_enabled'),
                'semester_second_enabled' => $request->boolean('semester_second_enabled'),
                'semester_summer_enabled' => $request->boolean('semester_summer_enabled'),
            ];
        }

        if (collect($settings)->filter()->count() !== 1) {
            return back()->withErrors([
                'semester_availability' => 'Choose exactly one active semester before saving.',
            ]);
        }

        $department->update($settings);

        return back()->with('success', 'Active semester updated successfully.');
    }
}
