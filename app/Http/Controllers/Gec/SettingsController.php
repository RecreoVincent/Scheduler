<?php

namespace App\Http\Controllers\Gec;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends GecController
{
    public function updateSemesters(Request $request): RedirectResponse
    {
        $activeSemester = $request->input('active_semester');

        if (! in_array($activeSemester, ['first', 'second', 'summer'], true)) {
            return back()->withErrors([
                'semester_availability' => 'Choose a valid semester before saving.',
            ]);
        }

        $request->session()->put('gec.active_semester', match ($activeSemester) {
            'first' => '1st',
            'second' => '2nd',
            'summer' => 'Summer',
        });

        return back()->with('success', 'Your semester view was updated for this browser.');
    }
}
