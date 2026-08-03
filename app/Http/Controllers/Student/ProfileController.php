<?php

namespace App\Http\Controllers\Student;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends StudentController
{
    public function edit(Request $request): View
    {
        return view('student.profile.edit', ['student' => $this->student($request)->load('academicSection')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $student = $this->student($request);
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($student->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        if ($student->email !== $validated['email']) {
            $student->email_verified_at = null;
        }

        $student->fill(collect($validated)->except(['current_password', 'password', 'password_confirmation'])->all());

        if (filled($validated['password'] ?? null)) {
            $student->password = Hash::make($validated['password']);
        }

        $student->save();

        return back()->with('success', 'Profile updated successfully.');
    }
}
