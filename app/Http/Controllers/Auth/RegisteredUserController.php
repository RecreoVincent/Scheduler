<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademicSection;
use App\Models\Department;
use App\Models\User;
use App\Models\Ms365StudentAccount;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly MicrosoftGraphMailService $graphMail)
    {
    }

    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $roles = ['dean', 'instructor', 'student'];
        $courses = Department::query()->orderBy('sort_order')->pluck('code')->all();
        $requestedRole = strtolower((string) old('role', $request->role));
        $requestedCourse = strtoupper((string) old('course', $request->course));
        $selectedRole = in_array($requestedRole, $roles, true)
            ? $requestedRole
            : 'student';
        $selectedCourse = in_array($requestedCourse, $courses, true)
            ? $requestedCourse
            : '';
        $sections = AcademicSection::query()
            ->select(['id', 'course', 'name', 'year_level', 'academic_year'])
            ->orderBy('course')
            ->orderBy('year_level')
            ->orderBy('name')
            ->get();

        return view('auth.register', compact('roles', 'courses', 'sections', 'selectedRole', 'selectedCourse'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in(['dean', 'instructor', 'student'])],
            'course' => ['required', Rule::exists('departments', 'code')],
            'year_level' => ['nullable', 'required_if:role,student', 'integer', 'between:1,4'],
            'academic_section_id' => [
                'nullable',
                'required_if:role,student',
                'integer',
                Rule::exists('academic_sections', 'id')->where(fn ($query) => $query
                    ->where('department_id', Department::query()->where('code', strtoupper((string) $request->course))->value('id'))
                    ->where('year_level', $request->integer('year_level'))),
            ],
            'employment_type' => ['nullable', 'required_if:role,instructor', Rule::in(['full_time', 'industry_part_time', 'flexible_part_time'])],
            'outside_work_end_time' => ['nullable', 'required_if:employment_type,industry_part_time', 'date_format:H:i'],
        ]);

        if ($validated['role'] === 'student' && ! Ms365StudentAccount::query()
            ->where('email', strtolower($validated['email']))
            ->where('is_blocked', false)
            ->whereNull('soft_deleted_at')
            ->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email is not an eligible MCC Microsoft 365 student account. Please use the MS365 email issued by the school.',
            ]);
        }

        if ($validated['role'] === 'student') {
            $this->startStudentOtpVerification($request, $validated);

            return redirect()->route('register.otp');
        }

        $user = $this->createUser($validated);

        event(new Registered($user));

        return redirect()
            ->route('login', ['role' => $validated['role'], 'course' => $validated['course']])
            ->with('success', 'Registration submitted. Your account is pending approval before you can sign in.');
    }

    public function otp(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('student_registration_otp')) {
            return redirect()->route('register', ['role'=>'student']);
        }

        $pending = $request->session()->get('student_registration_otp');
        return view('auth.register-otp', ['email'=>$pending['email'], 'expiresAt'=>$pending['expires_at']]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate(['otp'=>['required','digits:6']]);
        $pending = $request->session()->get('student_registration_otp');
        if (! $pending) return redirect()->route('register', ['role'=>'student'])->with('error','Your registration session has expired.');
        if (now()->timestamp > $pending['expires_at']) return back()->withErrors(['otp'=>'The verification code has expired. Request a new code.']);

        $attemptKey = 'student-registration-otp:'.$request->session()->getId();
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) return back()->withErrors(['otp'=>'Too many incorrect attempts. Please request a new code.']);
        if (! Hash::check($request->string('otp')->toString(), $pending['otp_hash'])) {
            RateLimiter::hit($attemptKey, 600);
            return back()->withErrors(['otp'=>'The verification code is incorrect.']);
        }

        $validated = $pending['registration'];
        if (User::where('email',$validated['email'])->exists()) {
            $request->session()->forget('student_registration_otp');
            return redirect()->route('login',['role'=>'student','course'=>$validated['course']])->with('error','An account with this email already exists.');
        }

        $user = $this->createUser($validated);
        event(new Registered($user));
        RateLimiter::clear($attemptKey);
        $request->session()->forget('student_registration_otp');

        return redirect()->route('login',['role'=>'student','course'=>$validated['course']])->with('success','Microsoft 365 email verified. Registration successful; you may now sign in.');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('student_registration_otp');
        if (! $pending) return redirect()->route('register',['role'=>'student']);
        if (now()->timestamp < ($pending['resend_at'] ?? 0)) return back()->withErrors(['otp'=>'Please wait before requesting another code.']);
        $this->sendOtp($request, $pending['registration']);
        return back()->with('success','A new verification code was sent to your Microsoft 365 email.');
    }

    private function startStudentOtpVerification(Request $request, array $validated): void
    {
        $validated['password'] = Hash::make($validated['password']);
        unset($validated['password_confirmation']);
        $this->sendOtp($request, $validated);
    }

    private function sendOtp(Request $request, array $registration): void
    {
        $code = (string) random_int(100000, 999999);
        $request->session()->put('student_registration_otp', [
            'email'=>$registration['email'], 'otp_hash'=>Hash::make($code),
            'expires_at'=>now()->addMinutes(10)->timestamp, 'resend_at'=>now()->addMinute()->timestamp,
            'registration'=>$registration,
        ]);
        $subject = 'MCC Scheduler registration verification code';
        $body = "Your MCC Scheduler student registration verification code is {$code}. This code expires in 10 minutes. Do not share it with anyone.";

        if ($this->graphMail->isConfigured()) {
            $this->graphMail->send($registration['email'], $subject, $body);
        } else {
            Mail::raw($body, function ($message) use ($registration, $subject) {
                $message->to($registration['email'])->subject($subject);
            });
        }
    }

    private function createUser(array $validated): User
    {
        $accountStatus = $validated['role'] === 'student' ? 'active' : 'pending';

        return User::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
            'email' => $validated['email'],
            'password' => $validated['role'] === 'student' ? $validated['password'] : Hash::make($validated['password']),
            'role' => $validated['role'],
            'course' => $validated['course'],
            'year_level' => $validated['role'] === 'student' ? $validated['year_level'] : null,
            'academic_section_id' => $validated['role'] === 'student' ? $validated['academic_section_id'] : null,
            'employment_type' => $validated['role'] === 'instructor' ? $validated['employment_type'] : null,
            'outside_work_end_time' => $validated['role'] === 'instructor' && $validated['employment_type'] === 'industry_part_time'
                ? $validated['outside_work_end_time'] : null,
            'account_status' => $accountStatus,
        ]);

    }
}
