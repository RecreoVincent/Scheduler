<?php

namespace App\Http\Controllers\Dean;

use App\Models\ClassSchedule;
use App\Models\Subject;
use App\Services\SubjectImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubjectController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $enabledSemesters = $this->enabledSemesters($request);
        $query = Subject::with('instructors')->forDepartment($course)->where('managed_by_gec', false)->whereIn('semester', $enabledSemesters);
        foreach (['year_level', 'semester', 'subject_type', 'curriculum'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        $subjectsByYear = $query
            ->orderBy('year_level')
            ->orderBy('code')
            ->get()
            ->groupBy('year_level');

        $editingSubject = null;
        if ($request->filled('edit')) {
            $editingSubject = Subject::forDepartment($course)->where('managed_by_gec', false)->find($request->input('edit'));
        }

        return view('dean.subjects.index', compact('course', 'subjectsByYear', 'enabledSemesters', 'editingSubject'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dean.subjects.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $course = $this->course($request);
        $semesters = $this->enabledSemesters($request);

        if ($semesters === []) {
            throw ValidationException::withMessages([
                'code' => 'Enable at least one semester for this department in Settings before adding subjects.',
            ]);
        }

        foreach ($semesters as $semester) {
            Subject::create(['course' => $course, 'semester' => $semester, ...$validated]);
        }

        return redirect()->route('dean.subjects.index')->with('success', 'Subject added successfully.');
    }

    public function import(Request $request, SubjectImporter $importer): RedirectResponse
    {
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        try {
            $result = $importer->import($request->file('csv_file')->getRealPath(), $this->course($request));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $response = back()->with('success', "Import complete: {$result['imported']} subject(s) created, {$result['skipped']} skipped.");

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
        $headers = ['code', 'name', 'subject_type', 'classification', 'year_level', 'semester', 'curriculum', 'units'];
        $sample = ['IT101', 'Introduction to Computing', 'Lecture', 'Major', '1', '1st', 'New', '3'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'subject-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function edit(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureCourse($request, $subject);
        $this->ensureNotGecManaged($subject);

        return redirect()->route('dean.subjects.index', ['edit' => $subject->id]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureCourse($request, $subject);
        $this->ensureNotGecManaged($subject);
        $validated = $this->validated($request, $subject);

        $subject->update($validated);

        return redirect()->route('dean.subjects.index')->with('success', 'Subject updated successfully.');
    }

    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureCourse($request, $subject);
        $this->ensureNotGecManaged($subject);
        ClassSchedule::withTrashed()->where('subject_id', $subject->id)->forceDelete();
        $subject->instructors()->detach();
        $subject->delete();

        return back()->with('success', 'Subject deleted successfully.');
    }

    /**
     * Subjects GEC originates on its own portal are exclusive to GEC — a
     * department's Dean must not be able to view, edit, or delete them here,
     * even by guessing the subject's URL directly.
     */
    private function ensureNotGecManaged(Subject $subject): void
    {
        abort_if($subject->managed_by_gec, 404);
    }

    private function validated(Request $request, ?Subject $subject = null): array
    {
        $course = $this->course($request);
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:150'],
            'subject_type' => ['required', Rule::in(['Lecture', 'Laboratory'])],
            'classification' => ['nullable', Rule::in(['Major', 'Minor'])],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'curriculum' => ['required', Rule::in(['New', 'Old'])],
            'units' => ['required', 'numeric', 'between:0.5,12'],
        ]);

        $codeAlreadyExists = Subject::query()
            ->forDepartment($course)
            ->where('curriculum', $validated['curriculum'])
            ->where('code', $validated['code'])
            ->when($subject?->exists, fn ($query) => $query->whereKeyNot($subject->id))
            ->exists();

        if ($codeAlreadyExists) {
            throw ValidationException::withMessages([
                'code' => "{$validated['code']} already exists in {$validated['curriculum']} Curriculum for {$course}. Choose another code or select the other curriculum.",
            ]);
        }

        $validated['classification'] ??= 'Major';

        return $validated;
    }
}
