<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PortalAccountAccessController extends Controller
{
    private const REGISTRATION_SESSION = 'portal_id_registration';

    /**
     * Check whether the entered student or instructor ID already has a portal
     * account. IDs that have not yet been claimed continue to registration.
     */
    public function identify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['instructor', 'student'])],
            'portal_id' => ['required', 'string', 'max:30'],
        ]);

        $role = $validated['role'];
        $portalId = trim($validated['portal_id']);
        $account = $this->accountFor($role, $portalId);

        if ($role === 'student' && ! StudentRoster::query()->where('student_id', $portalId)->exists()) {
            throw $this->invalidId($role);
        }

        if ($role === 'instructor' && ! $account) {
            throw $this->invalidId($role);
        }

        if ($account && $account->role !== $role) {
            throw $this->invalidId($role);
        }

        if ($account && filled($account->username)) {
            return redirect()->route('login', [
                'role' => $role,
                'portal_id' => $portalId,
                'step' => 'sign-in',
            ]);
        }

        $request->session()->put(self::REGISTRATION_SESSION, [
            'role' => $role,
            'portal_id' => $portalId,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        return redirect()->route('register', ['role' => $role, 'portal_id' => $portalId]);
    }

    /**
     * Keep the old student endpoint compatible while using the new ID-first
     * account flow.
     */
    public function identifyStudent(Request $request): RedirectResponse
    {
        $request->merge([
            'role' => 'student',
            'portal_id' => $request->input('portal_id', $request->input('student_id')),
        ]);

        return $this->identify($request);
    }

    private function accountFor(string $role, string $portalId): ?User
    {
        $column = $role === 'instructor' ? 'instructor_id' : 'student_id';

        return User::withTrashed()->where($column, $portalId)->first();
    }

    private function invalidId(string $role): ValidationException
    {
        $label = $role === 'instructor' ? 'Instructor ID' : 'Student ID';

        return ValidationException::withMessages([
            'portal_id' => "That {$label} is not eligible for a portal account. Please check the ID or contact the school office.",
        ]);
    }
}
