<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSection;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private array $roles = [
        'dean',
        'instructor',
        'student',
    ];

    private array $courses = [
        'BSIT',
        'BSBA',
        'BSHM',
        'BSED',
        'BEED',
    ];

    public function index(Request $request)
    {
        $query = User::query()
            ->whereIn('role', $this->roles);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($userQuery) use ($search) {
                $userQuery
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('suffix', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('course', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('course')) {
            $query->where('course', $request->course);
        }

        $users = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $this->roles,
            'courses' => $this->courses,
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => $this->roles,
            'courses' => $this->courses,
            'sections' => AcademicSection::orderBy('course')->orderBy('year_level')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'role' => [
                'required',
                Rule::in($this->roles),
            ],
            'course' => [
                'required',
                Rule::in($this->courses),
            ],
            'year_level' => ['nullable', 'required_if:role,student', 'integer', 'between:1,4'],
            'academic_section_id' => [
                'nullable',
                Rule::exists('academic_sections', 'id')->where(fn ($query) => $query
                    ->where('course', $request->course)
                    ->where('year_level', $request->year_level)),
            ],
            'employment_type' => ['nullable', 'required_if:role,instructor', Rule::in(['full_time', 'industry_part_time', 'flexible_part_time', 'part_time'])],
            'outside_work_end_time' => ['nullable', 'required_if:employment_type,industry_part_time', 'date_format:H:i'],
            'account_status' => ['required', Rule::in(['active', 'pending'])],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        try {
            User::create([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'email' => $validated['email'],
                'role' => $validated['role'],
                'course' => $validated['course'],
                'year_level' => $validated['role'] === 'student' ? $validated['year_level'] : null,
                'academic_section_id' => $validated['role'] === 'student' ? ($validated['academic_section_id'] ?? null) : null,
                'employment_type' => $validated['role'] === 'instructor' ? $validated['employment_type'] : null,
                'outside_work_end_time' => $validated['role'] === 'instructor' && $validated['employment_type'] === 'industry_part_time'
                    ? $validated['outside_work_end_time'] : null,
                'account_status' => $validated['account_status'],
                'password' => Hash::make($validated['password']),
            ]);
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'The account could not be created. Please try again.');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Account created successfully.');
    }

    public function edit(User $user)
    {
        abort_if($user->role === 'admin', 403);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->roles,
            'courses' => $this->courses,
            'sections' => AcademicSection::orderBy('course')->orderBy('year_level')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->role === 'admin', 403);

        $validated = $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => [
                'required',
                Rule::in($this->roles),
            ],
            'course' => [
                'required',
                Rule::in($this->courses),
            ],
            'year_level' => ['nullable', 'required_if:role,student', 'integer', 'between:1,4'],
            'academic_section_id' => [
                'nullable',
                Rule::exists('academic_sections', 'id')->where(fn ($query) => $query
                    ->where('course', $request->course)
                    ->where('year_level', $request->year_level)),
            ],
            'employment_type' => ['nullable', 'required_if:role,instructor', Rule::in(['full_time', 'industry_part_time', 'flexible_part_time', 'part_time'])],
            'outside_work_end_time' => ['nullable', 'required_if:employment_type,industry_part_time', 'date_format:H:i'],
            'account_status' => ['required', Rule::in(['active', 'pending'])],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user->first_name = $validated['first_name'];
        $user->middle_name = $validated['middle_name'] ?? null;
        $user->last_name = $validated['last_name'];
        $user->suffix = $validated['suffix'] ?? null;
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->course = $validated['course'];
        $user->year_level = $validated['role'] === 'student' ? $validated['year_level'] : null;
        $user->academic_section_id = $validated['role'] === 'student' ? ($validated['academic_section_id'] ?? null) : null;
        $user->employment_type = $validated['role'] === 'instructor' ? $validated['employment_type'] : null;
        $user->outside_work_end_time = $validated['role'] === 'instructor' && $validated['employment_type'] === 'industry_part_time'
            ? $validated['outside_work_end_time'] : null;
        $user->account_status = $validated['account_status'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        try {
            $user->save();
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'The account could not be updated. Please try again.');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Account updated successfully.');
    }

    public function destroy(User $user)
    {
        abort_if($user->role === 'admin', 403);

        try {
            $user->delete();
        } catch (QueryException $exception) {
            report($exception);

            return back()->with('error', 'The account could not be deleted. Please try again.');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Account deleted successfully.');
    }
}
