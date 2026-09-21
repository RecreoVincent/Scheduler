<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PortalSemesterPreferenceController extends Controller
{
    public function updateInstructor(Request $request): RedirectResponse
    {
        return $this->update($request, 'instructor');
    }

    public function updateStudent(Request $request): RedirectResponse
    {
        return $this->update($request, 'student');
    }

    private function update(Request $request, string $role): RedirectResponse
    {
        abort_unless($request->user()?->role === $role, 403);

        $validated = $request->validate([
            'semester' => ['required', 'string', Rule::in(['1st', '2nd', 'Summer'])],
        ]);

        $request->session()->put("{$role}_viewing_semester", $validated['semester']);

        $label = match ($validated['semester']) {
            '1st' => 'First Semester',
            '2nd' => 'Second Semester',
            default => 'Summer',
        };

        return back()->with('success', "Now viewing {$label} schedules.");
    }
}
