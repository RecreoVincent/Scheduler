<?php

namespace App\Http\Controllers\Dean;

use App\Models\ClassSchedule;
use App\Models\User;
use App\Services\InstructorAccountImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstructorController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $query = User::forDepartment($course)->where('role', 'instructor');

        $pendingInstructors = (clone $query)
            ->where('account_status', 'pending')
            ->orderBy('created_at')
            ->get();

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        $instructors = (clone $query)->where('account_status', 'active');

        match ($request->input('sort', 'name')) {
            'newest' => $instructors->latest(),
            'oldest' => $instructors->oldest(),
            'employment' => $instructors
                ->orderByRaw("CASE employment_type WHEN 'full_time' THEN 1 WHEN 'flexible_part_time' THEN 2 WHEN 'industry_part_time' THEN 3 ELSE 4 END")
                ->orderBy('first_name')
                ->orderBy('last_name'),
            default => $instructors->orderBy('first_name')->orderBy('middle_name')->orderBy('last_name'),
        };

        $instructors = $instructors->paginate(12)->withQueryString();

        $editingInstructor = null;
        if ($request->filled('edit')) {
            $editingInstructor = User::forDepartment($course)
                ->where('role', 'instructor')
                ->find($request->input('edit'));
        }

        return view('dean.instructors.index', compact('course', 'pendingInstructors', 'instructors', 'editingInstructor'));
    }

    public function create(Request $request): View
    {
        return view('dean.instructors.form', ['course' => $this->course($request), 'instructor' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'role' => 'instructor',
            'course' => $this->course($request),
            'account_status' => 'active',
        ]);

        return redirect()->route('dean.instructors.index')->with('success', 'Instructor account created successfully.');
    }

    public function edit(Request $request, User $instructor): View
    {
        $this->ensureInstructor($request, $instructor);

        return view('dean.instructors.form', ['course' => $this->course($request), 'instructor' => $instructor]);
    }

    public function update(Request $request, User $instructor): RedirectResponse
    {
        $this->ensureInstructor($request, $instructor);
        $validated = $this->validated($request, $instructor);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $instructor->update($validated);

        return redirect()->route('dean.instructors.index')->with('success', 'Instructor account updated successfully.');
    }

    public function import(Request $request, InstructorAccountImporter $importer): RedirectResponse
    {
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        try {
            $result = $importer->import($request->file('csv_file')->getRealPath(), $this->course($request));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = "Import complete: {$result['imported']} instructor(s) created, {$result['skipped']} skipped.";

        if ($result['generated'] !== []) {
            $credentials = collect($result['generated'])
                ->map(fn (array $entry) => "{$entry['email']} (temporary password: {$entry['password']})")
                ->implode('; ');
            $message .= " No password was given for some rows, so one was generated — share these with the instructor: {$credentials}";
        }

        $response = back()->with('success', $message);

        if ($result['errors'] !== []) {
            $shown = array_slice($result['errors'], 0, 15);
            $note = implode(' | ', $shown);
            if (count($result['errors']) > 15) {
                $note .= ' | +'.(count($result['errors']) - 15).' more.';
            }
            $response->with('error_note', $note);
        }

        return $response;
    }

    public function importTemplate(): StreamedResponse
    {
        $headers = ['first_name', 'middle_name', 'last_name', 'suffix', 'email', 'employment_type', 'outside_work_end_time', 'password'];
        $sample = ['Juan', '', 'Dela Cruz', '', 'juan.delacruz@example.com', 'full_time', '', ''];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'instructor-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function approve(Request $request, User $instructor): RedirectResponse
    {
        $this->ensureInstructor($request, $instructor);
        abort_unless($instructor->account_status === 'pending', 422, 'This instructor account is not pending.');

        $instructor->update(['account_status' => 'active']);

        return back()->with('success', 'Instructor account approved successfully.');
    }

    public function destroy(Request $request, User $instructor): RedirectResponse
    {
        $this->ensureInstructor($request, $instructor);
        $wasPending = $instructor->account_status === 'pending';

        DB::table('subject_instructor')->where('instructor_id', $instructor->id)->delete();
        ClassSchedule::withTrashed()->where('instructor_id', $instructor->id)->forceDelete();
        $instructor->delete();

        return back()->with('success', $wasPending
            ? 'Pending instructor registration declined.'
            : 'Instructor account deleted successfully.');
    }

    private function ensureInstructor(Request $request, User $instructor): void
    {
        abort_unless($instructor->role === 'instructor' && (int) $instructor->department_id === (int) $request->user()->department_id, 404);
    }

    private function validated(Request $request, ?User $instructor = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($instructor?->id)],
            'employment_type' => ['required', Rule::in(['full_time', 'industry_part_time', 'flexible_part_time'])],
            'outside_work_end_time' => ['nullable', 'required_if:employment_type,industry_part_time', 'date_format:H:i'],
            'password' => [$instructor?->exists ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);
    }
}
