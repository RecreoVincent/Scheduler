<?php

namespace App\Http\Controllers\Instructor;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends InstructorController
{
    public function edit(Request $request): View
    {
        return view('instructor.profile.edit', ['instructor' => $this->instructor($request)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $instructor = $this->instructor($request);
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($instructor->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        if ($instructor->email !== $validated['email']) {
            $instructor->email_verified_at = null;
        }

        $instructor->fill(collect($validated)->except(['current_password', 'password', 'password_confirmation'])->all());

        if (filled($validated['password'] ?? null)) {
            $instructor->password = Hash::make($validated['password']);
        }

        $instructor->save();

        return back()->with('success', 'Profile updated successfully.');
    }
}
