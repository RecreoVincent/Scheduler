<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademicSection;
use App\Models\Department;
use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    private const REGISTRATION_SESSION = 'portal_id_registration';
    private const OTP_SESSION = 'portal_registration_otp';

    public function create(Request $request): View|RedirectResponse
    {
        $portalRegistration = $this->portalRegistration($request);
        $requestedRole = strtolower((string) old('role', $request->role));

        if (in_array($requestedRole, ['instructor', 'student'], true)
            && (! $portalRegistration || $portalRegistration['role'] !== $requestedRole)) {
            return redirect()->route('login', ['role' => $requestedRole])
                ->with('error', 'Enter your ID first so we can prepare the correct registration form.');
        }

        $roles = $portalRegistration ? [$portalRegistration['role']] : ['dean'];
        $courses = Department::query()->orderBy('sort_order')->pluck('code')->all();
        $selectedRole = $portalRegistration['role'] ?? 'dean';
        $selectedCourse = strtoupper((string) old('course', $request->course));
        $sections = AcademicSection::query()
            ->select(['id', 'course', 'name', 'year_level', 'academic_year'])
            ->orderBy('course')->orderBy('year_level')->orderBy('name')->get();

        return view('auth.register', compact(
            'roles', 'courses', 'sections', 'selectedRole', 'selectedCourse', 'portalRegistration',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($portalRegistration = $this->portalRegistration($request)) {
            return $this->startPortalRegistration($request, $portalRegistration);
        }

        $role = strtolower($request->string('role')->toString());
        if (in_array($role, ['instructor', 'student'], true)) {
            return redirect()->route('login', ['role' => $role])
                ->with('error', 'Enter your ID first so we can prepare the correct registration form.');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in(['dean'])],
            'course' => ['required', Rule::exists('departments', 'code')],
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'dean',
            'course' => $validated['course'],
            'account_status' => 'pending',
        ]);

        event(new Registered($user));

        return redirect()->route('login', ['role' => 'dean', 'course' => $validated['course']])
            ->with('success', 'Registration submitted. Your account is pending approval before you can sign in.');
    }

    public function otp(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::OTP_SESSION)) {
            return redirect()->route('home');
        }

        $pending = $request->session()->get(self::OTP_SESSION);

        return view('auth.register-otp', [
            'email' => $pending['email'],
            'role' => $pending['registration']['role'],
            'expiresAt' => $pending['expires_at'],
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate(['otp' => ['required', 'digits:6']]);
        $pending = $request->session()->get(self::OTP_SESSION);

        if (! $pending) {
            return redirect()->route('home')->with('error', 'Your registration session has expired. Please start again.');
        }
        if (now()->timestamp > $pending['expires_at']) {
            return back()->withErrors(['otp' => 'The verification code has expired. Start again to request a new code.']);
        }

        $attemptKey = 'portal-registration-otp:'.$request->session()->getId();
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return back()->withErrors(['otp' => 'Too many incorrect attempts. Please request a new code.']);
        }
        if (! Hash::check($request->string('otp')->toString(), $pending['otp_hash'])) {
            RateLimiter::hit($attemptKey, 600);

            return back()->withErrors(['otp' => 'The verification code is incorrect.']);
        }

        $registration = $pending['registration'];
        $user = $this->completePortalRegistration($registration);

        event(new Registered($user));
        RateLimiter::clear($attemptKey);
        $request->session()->forget([self::OTP_SESSION, self::REGISTRATION_SESSION]);

        return redirect()->route('login', [
            'role' => $registration['role'],
            'portal_id' => $registration['portal_id'],
            'step' => 'sign-in',
        ])->with('success', 'Email verified. Your account has been created successfully. You can now sign in.');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::OTP_SESSION);
        if (! $pending) {
            return redirect()->route('home')->with('error', 'Your registration session has expired. Please start again.');
        }
        if (now()->timestamp < ($pending['resend_at'] ?? 0)) {
            return back()->withErrors(['otp' => 'Please wait before requesting another code.']);
        }

        try {
            $this->sendOtp($request, $pending['registration']);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'We could not resend the verification code right now. Please try again in a moment.');
        }

        return back()->with('success', 'A new verification code was sent to your Gmail address.');
    }

    /** @param array{role:string,portal_id:string,expires_at:int} $portalRegistration */
    private function startPortalRegistration(Request $request, array $portalRegistration): RedirectResponse
    {
        $role = $portalRegistration['role'];
        $portalId = $portalRegistration['portal_id'];
        $account = $this->portalAccount($role, $portalId);

        if (($role === 'instructor' && ! $account) || ($account && $account->role !== $role)) {
            return redirect()->route('login', ['role' => $role])
                ->with('error', 'That ID is no longer available. Please contact the school office.');
        }
        if ($role === 'student' && ! StudentRoster::query()->where('student_id', $portalId)->exists()) {
            return redirect()->route('login', ['role' => $role])
                ->with('error', 'That Student ID is no longer in the official student roster.');
        }

        $validated = $request->validate([
            'username' => [
                'required', 'string', 'lowercase', 'min:3', 'max:50', 'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($account?->id),
            ],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255', 'ends_with:@gmail.com',
                Rule::unique('users', 'email')->ignore($account?->id),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $this->sendOtp($request, [
                'role' => $role,
                'portal_id' => $portalId,
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput($request->except(['password', 'password_confirmation']))->with(
                'error',
                'We could not send the verification code to that Gmail address right now. Please try again in a moment.',
            );
        }

        return redirect()->route('register.otp');
    }

    /** @param array{role:string,portal_id:string,username:string,email:string,password:string} $registration */
    private function completePortalRegistration(array $registration): User
    {
        $role = $registration['role'];
        $portalId = $registration['portal_id'];
        $account = $this->portalAccount($role, $portalId);

        if ($account && $account->role !== $role) {
            throw ValidationException::withMessages(['otp' => 'This ID belongs to a different portal account.']);
        }
        if (User::withTrashed()->where('username', $registration['username'])->when($account, fn ($query) => $query->where('id', '!=', $account->id))->exists()) {
            throw ValidationException::withMessages(['otp' => 'That username is no longer available. Start again and choose another one.']);
        }
        if (User::withTrashed()->where('email', $registration['email'])->when($account, fn ($query) => $query->where('id', '!=', $account->id))->exists()) {
            throw ValidationException::withMessages(['otp' => 'That Gmail address is already used by another account.']);
        }

        if ($role === 'instructor') {
            if (! $account) {
                throw ValidationException::withMessages(['otp' => 'This Instructor ID is no longer available.']);
            }
            if ($account->trashed()) {
                $account->restore();
            }
            $account->forceFill([
                'username' => $registration['username'],
                'email' => $registration['email'],
                'password' => $registration['password'],
                'account_status' => 'active',
                'email_verified_at' => now(),
            ])->save();

            return $account;
        }

        $roster = StudentRoster::query()->where('student_id', $portalId)->first();
        if (! $roster) {
            throw ValidationException::withMessages(['otp' => 'This Student ID is no longer in the official student roster.']);
        }
        if ($account?->trashed()) {
            $account->restore();
        }

        [$firstName, $middleName, $lastName] = $this->nameParts($roster->full_name);
        $section = $this->matchingSection($roster);
        $course = strtoupper((string) ($roster->course ?? $section?->course ?? $account?->course));
        $account ??= new User(['role' => 'student', 'student_id' => $portalId]);
        $account->forceFill([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'username' => $registration['username'],
            'email' => $registration['email'],
            'password' => $registration['password'],
            'role' => 'student',
            'course' => $course !== '' ? $course : null,
            'year_level' => $section?->year_level ?? $account->year_level,
            'academic_section_id' => $section?->id ?? $account->academic_section_id,
            'student_id' => $portalId,
            'account_status' => 'active',
            'email_verified_at' => now(),
        ])->save();

        return $account;
    }

    /** @param array{role:string,portal_id:string,username:string,email:string,password:string} $registration */
    private function sendOtp(Request $request, array $registration): void
    {
        $code = (string) random_int(100000, 999999);
        $request->session()->put(self::OTP_SESSION, [
            'email' => $registration['email'],
            'otp_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'resend_at' => now()->addMinute()->timestamp,
            'registration' => $registration,
        ]);

        $label = $registration['role'] === 'instructor' ? 'Instructor' : 'Student';
        $subject = 'MCC Scheduler registration verification code';
        $body = "Your MCC Scheduler {$label} Portal verification code is {$code}. This code expires in 10 minutes. Do not share it with anyone.";

        Mail::raw($body, function ($message) use ($registration, $subject): void {
            $message->to($registration['email'])->subject($subject);
        });
    }

    /** @return array{role:string,portal_id:string,expires_at:int}|null */
    private function portalRegistration(Request $request): ?array
    {
        $registration = $request->session()->get(self::REGISTRATION_SESSION);
        if (! is_array($registration)
            || ! in_array($registration['role'] ?? null, ['instructor', 'student'], true)
            || blank($registration['portal_id'] ?? null)
            || now()->timestamp > ($registration['expires_at'] ?? 0)) {
            $request->session()->forget(self::REGISTRATION_SESSION);

            return null;
        }

        return $registration;
    }

    private function portalAccount(string $role, string $portalId): ?User
    {
        return User::withTrashed()->where(
            $role === 'instructor' ? 'instructor_id' : 'student_id',
            $portalId,
        )->first();
    }

    private function matchingSection(StudentRoster $roster): ?AcademicSection
    {
        if (blank($roster->section)) {
            return null;
        }

        $sectionKey = Str::lower(preg_replace('/[\s-]+/', '', trim($roster->section)) ?? '');
        $sections = AcademicSection::query()
            ->when(filled($roster->course), fn ($query) => $query->where('course', $roster->course))
            ->whereRaw("LOWER(REPLACE(REPLACE(name, ' ', ''), '-', '')) = ?", [$sectionKey])
            ->orderByDesc('academic_year')->get();

        if (blank($roster->course) && $sections->pluck('course')->filter()->unique()->count() > 1) {
            return null;
        }

        return $sections->first();
    }

    /** @return array{0:string,1:?string,2:string} */
    private function nameParts(string $fullName): array
    {
        $fullName = Str::squish($fullName);
        if (str_contains($fullName, ',')) {
            [$lastName, $remainingNames] = array_map('trim', explode(',', $fullName, 2));
            $parts = preg_split('/\s+/', $remainingNames) ?: [];

            return [$parts[0] ?? $lastName, count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null, $lastName];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        $firstName = array_shift($parts) ?: 'Student';
        $lastName = array_pop($parts) ?: $firstName;

        return [$firstName, $parts === [] ? null : implode(' ', $parts), $lastName];
    }
}
