<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademicSection;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $roles = ['dean', 'instructor', 'student'];
        $courses = ['BSIT', 'BSBA', 'BSHM', 'BSED', 'BEED'];
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
            'course' => ['required', Rule::in(['BSIT', 'BSBA', 'BSHM', 'BSED', 'BEED'])],
            'year_level' => ['nullable', 'required_if:role,student', 'integer', 'between:1,4'],
            'academic_section_id' => [
                'nullable',
                'required_if:role,student',
                'integer',
                Rule::exists('academic_sections', 'id')->where(fn ($query) => $query
                    ->where('course', strtoupper((string) $request->course))
                    ->where('year_level', $request->integer('year_level'))),
            ],
            'employment_type' => ['nullable', 'required_if:role,instructor', Rule::in(['full_time', 'industry_part_time', 'flexible_part_time'])],
            'outside_work_end_time' => ['nullable', 'required_if:employment_type,industry_part_time', 'date_format:H:i'],
        ]);

        $accountStatus = $validated['role'] === 'student' ? 'active' : 'pending';

        $user = User::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'course' => $validated['course'],
            'year_level' => $validated['role'] === 'student' ? $validated['year_level'] : null,
            'academic_section_id' => $validated['role'] === 'student' ? $validated['academic_section_id'] : null,
            'employment_type' => $validated['role'] === 'instructor' ? $validated['employment_type'] : null,
            'outside_work_end_time' => $validated['role'] === 'instructor' && $validated['employment_type'] === 'industry_part_time'
                ? $validated['outside_work_end_time'] : null,
            'account_status' => $accountStatus,
        ]);

        event(new Registered($user));

        return redirect()
            ->route('login', ['role' => $validated['role'], 'course' => $validated['course']])
            ->with('success', $validated['role'] === 'student'
                ? 'Registration successful. You may now sign in to the Student Portal.'
                : 'Registration submitted. Your account is pending approval before you can sign in.');
    }
}
