<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $selectedRole = strtolower($request->string('role')->toString());

        if (! in_array($selectedRole, ['admin', 'dean', 'instructor', 'student'], true)) {
            return redirect()->route('home');
        }

        if ($selectedRole === 'dean') {
            $selectedCourse = strtoupper($request->string('course')->toString());

            if (! in_array($selectedCourse, ['BSIT', 'BSBA', 'BSHM', 'BSED', 'BEED'], true)) {
                return redirect()->route('home')->withFragment('portals');
            }
        }

        if (Auth::guard($selectedRole)->check()) {
            return redirect()->route("{$selectedRole}.dashboard");
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $guard = $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->account_status === 'pending') {
            Auth::guard($guard)->logout();

            throw ValidationException::withMessages([
                'email' => 'Your account is still pending approval.',
            ]);
        }

        $selectedRole = strtolower((string) $request->input('role'));
        $accountRole = strtolower((string) ($user->role ?? 'user'));
        $selectedCourse = strtoupper((string) $request->input('course'));
        $accountCourse = strtoupper((string) ($user->course ?? ''));

        if (in_array($selectedRole, ['admin', 'dean', 'instructor', 'student'], true)
            && $selectedRole !== $accountRole) {
            Auth::guard($guard)->logout();

            throw ValidationException::withMessages([
                'email' => 'These credentials do not belong to the selected portal.',
            ]);
        }

        if ($selectedRole === 'dean' && $selectedCourse !== '' && $selectedCourse !== $accountCourse) {
            Auth::guard($guard)->logout();

            throw ValidationException::withMessages([
                'email' => 'This dean account is not assigned to the selected course.',
            ]);
        }

        return in_array($selectedRole, ['admin', 'dean', 'instructor', 'student'], true)
            ? redirect()->route("{$selectedRole}.dashboard")
            : redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $role = strtolower($request->string('role')->toString());
        $guard = in_array($role, ['admin', 'dean', 'instructor', 'student'], true) ? $role : 'web';
        Auth::guard($guard)->logout();

        return redirect('/');
    }
}
