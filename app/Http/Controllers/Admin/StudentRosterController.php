<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StudentRoster;
use App\Models\User;
use App\Services\StudentRosterImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class StudentRosterController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $roster = StudentRoster::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('student_id', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")
                ->orWhere('section', 'like', "%{$search}%")
                ->orWhere('course', 'like', "%{$search}%")))
            ->orderBy('full_name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $registeredStudentIds = User::query()
            ->where('role', 'student')
            ->whereNotNull('student_id')
            ->pluck('student_id')
            ->all();

        $statistics = [
            'total' => StudentRoster::count(),
            'registered' => StudentRoster::whereIn('student_id', $registeredStudentIds)->count(),
        ];

        $editingRoster = $request->filled('edit')
            ? StudentRoster::find($request->integer('edit'))
            : null;
        $courses = Department::query()->orderBy('sort_order')->pluck('code')->all();

        return view('admin.student-roster.index', compact(
            'roster',
            'statistics',
            'search',
            'registeredStudentIds',
            'editingRoster',
            'courses',
        ));
    }

    public function store(Request $request, StudentRosterImporter $importer): RedirectResponse
    {
        $roster = StudentRoster::create([
            ...$this->validated($request),
            'imported_at' => now(),
        ]);
        $importer->syncRosterRecord($roster);

        return redirect()->route('admin.student-roster.index')->with('success', 'Student roster record created successfully.');
    }

    public function update(Request $request, StudentRoster $studentRoster, StudentRosterImporter $importer): RedirectResponse
    {
        $previousStudentId = $studentRoster->student_id;
        $studentRoster->update($this->validated($request, $studentRoster));
        $importer->syncRosterRecord($studentRoster, $previousStudentId);

        return redirect()->route('admin.student-roster.index')->with('success', 'Student roster record updated successfully.');
    }

    public function destroy(StudentRoster $studentRoster): RedirectResponse
    {
        $studentRoster->delete();

        return redirect()->route('admin.student-roster.index')
            ->with('success', 'Student roster record removed. Existing student portal accounts were not deleted.');
    }

    public function import(Request $request, StudentRosterImporter $importer): RedirectResponse
    {
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:51200']]);

        try {
            $result = $importer->import($request->file('csv_file')->getRealPath());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $response = back()->with('success', "Student roster updated: {$result['imported']} imported, {$result['skipped']} skipped.");

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

    /** @return array{student_id:string,full_name:string,section:?string,course:string} */
    private function validated(Request $request, ?StudentRoster $studentRoster = null): array
    {
        $request->merge([
            'student_id' => Str::upper(Str::squish((string) $request->input('student_id'))),
            'full_name' => Str::squish((string) $request->input('full_name')),
            'section' => ($section = Str::squish((string) $request->input('section'))) === '' ? null : $section,
            'course' => Str::upper(Str::squish((string) $request->input('course'))),
        ]);

        return $request->validate([
            'student_id' => ['required', 'string', 'max:30', Rule::unique('student_rosters', 'student_id')->ignore($studentRoster?->id)],
            'full_name' => ['required', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:100'],
            'course' => ['required', Rule::in(Department::query()->pluck('code')->all())],
        ]);
    }
}
