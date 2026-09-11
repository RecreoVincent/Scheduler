<?php

namespace App\Http\Controllers\Dean;

use App\Models\AcademicSection;
use App\Models\User;
use App\Services\StudentAccountImporter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $query = User::forDepartment($course)->where('role', 'student');

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('middle_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($request->filled('year_level')) {
            $query->where('year_level', $request->input('year_level'));
        }

        if ($request->filled('academic_section_id')) {
            $query->where('academic_section_id', $request->input('academic_section_id'));
        }

        match ($request->input('sort', 'name')) {
            'newest' => $query->latest(),
            'oldest' => $query->oldest(),
            'year_level' => $query->orderBy('year_level')->orderBy('first_name')->orderBy('last_name'),
            default => $query->orderBy('first_name')->orderBy('middle_name')->orderBy('last_name'),
        };

        $students = $query->with('academicSection')->paginate(15)->withQueryString();

        $sections = AcademicSection::forDepartment($course)
            ->when($request->filled('year_level'), fn ($q) => $q->where('year_level', $request->input('year_level')))
            ->orderBy('year_level')
            ->orderBy('name')
            ->get(['id', 'name', 'year_level']);
        $allSections = $this->allSections($request);

        $editingStudent = null;
        if ($request->filled('edit')) {
            $editingStudent = User::forDepartment($course)->where('role', 'student')->find($request->input('edit'));
        }

        $studentAccountCount = User::forDepartment($course)->where('role', 'student')->count();

        return view('dean.students.index', compact('course', 'students', 'sections', 'allSections', 'editingStudent', 'studentAccountCount'));
    }

    public function create(Request $request): View
    {
        return view('dean.students.form', [
            'course' => $this->course($request),
            'student' => new User,
            'sections' => $this->allSections($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'course' => $this->course($request),
            'account_status' => 'active',
        ]);

        return redirect()->route('dean.students.index')->with('success', 'Student account created successfully.');
    }

    public function import(Request $request, StudentAccountImporter $importer): RedirectResponse
    {
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        try {
            $result = $importer->import($request->file('csv_file')->getRealPath(), $this->course($request));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = "Import complete: {$result['imported']} student(s) created, {$result['skipped']} skipped.";

        if ($result['generated'] !== []) {
            $credentials = collect($result['generated'])
                ->map(fn (array $entry) => "{$entry['email']} (temporary password: {$entry['password']})")
                ->implode('; ');
            $message .= " No password was given for some rows, so one was generated — share these with the student: {$credentials}";
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
        $headers = ['first_name', 'middle_name', 'last_name', 'suffix', 'email', 'year_level', 'section', 'password'];
        $sample = ['Maria', '', 'Santos', '', 'maria.santos@example.com', '1', '', ''];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'student-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function edit(Request $request, User $student): View
    {
        $this->ensureStudent($request, $student);

        return view('dean.students.form', [
            'course' => $this->course($request),
            'student' => $student,
            'sections' => $this->allSections($request),
        ]);
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        $this->ensureStudent($request, $student);
        $validated = $this->validated($request, $student);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $student->update($validated);

        return redirect()->route('dean.students.index')->with('success', 'Student account updated successfully.');
    }

    public function destroy(Request $request, User $student): RedirectResponse
    {
        $this->ensureStudent($request, $student);
        $student->delete();

        return back()->with('success', 'Student account deleted successfully.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $course = $this->course($request);
        $studentIds = User::forDepartment($course)->where('role', 'student')->pluck('id');

        if ($studentIds->isEmpty()) {
            return back()->with('error', "There are no {$course} student accounts to remove.");
        }

        $removedCount = User::whereIn('id', $studentIds)->delete();

        return redirect()->route('dean.students.index')->with(
            'success',
            "All {$course} student accounts were removed successfully ({$removedCount} ".str('account')->plural($removedCount).').',
        );
    }

    private function ensureStudent(Request $request, User $student): void
    {
        abort_unless($student->role === 'student' && (int) $student->department_id === (int) $request->user()->department_id, 404);
    }

    private function allSections(Request $request): Collection
    {
        return AcademicSection::forDepartment($this->course($request))
            ->orderBy('year_level')
            ->orderBy('name')
            ->get(['id', 'name', 'year_level']);
    }

    private function validated(Request $request, ?User $student = null): array
    {
        $course = $this->course($request);
        $request->merge(['academic_section_id' => $request->input('academic_section_id') ?: null]);

        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student?->id)],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'academic_section_id' => [
                'nullable',
                Rule::exists('academic_sections', 'id')->where(fn ($q) => $q
                    ->where('course', $course)
                    ->where('year_level', $request->input('year_level'))),
            ],
            'password' => [$student?->exists ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);
    }
}
