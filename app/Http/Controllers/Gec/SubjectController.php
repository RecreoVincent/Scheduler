<?php

namespace App\Http\Controllers\Gec;

use App\Models\ClassSchedule;
use App\Models\Subject;
use App\Services\SubjectImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubjectController extends GecController
{
    public function index(Request $request): View
    {
        $enabledSemesters = $this->enabledSemesters($request);
        $query = $this->minorSubjects()->with('instructors')->whereIn('semester', $enabledSemesters);

        if ($request->filled('department')) {
            $query->where('course', $request->input('department'));
        }
        foreach (['year_level', 'subject_type'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        $subjects = $query
            ->orderBy('course')
            ->orderBy('year_level')
            ->orderBy('code')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $editingSubject = null;
        if ($request->filled('edit')) {
            $editingSubject = $this->minorSubjects()->find($request->input('edit'));
        }

        return view('gec.subjects.index', compact('subjects', 'editingSubject'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('gec.subjects.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $semesters = $this->enabledSemesters($request);

        if ($semesters === []) {
            throw ValidationException::withMessages([
                'code' => 'Enable at least one semester in Settings before adding subjects.',
            ]);
        }

        // Subjects GEC originates itself stay exclusive to the GEC portal —
        // the owning department's own Subjects page must not show or manage
        // them. Subjects already created by a department's own Dean (before
        // GEC existed, or independently of it) are untouched by this flag
        // and keep showing on that Dean's page as before.
        foreach ($semesters as $semester) {
            Subject::create([...$validated, 'semester' => $semester, 'managed_by_gec' => true]);
        }

        return redirect()->route('gec.subjects.index')->with('success', 'Minor subject added successfully.');
    }

    public function import(Request $request, SubjectImporter $importer): RedirectResponse
    {
        $validated = $request->validate([
            'import_course' => ['required', Rule::in(self::REAL_DEPARTMENTS)],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        try {
            $result = $importer->import($request->file('csv_file')->getRealPath(), $validated['import_course'], true);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $response = back()->with('success', "Import complete: {$result['imported']} minor subject(s) created, {$result['skipped']} skipped.");

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
        $sample = ['GE101', 'Understanding the Self', 'Lecture', 'Minor', '1', '1st', 'New', '3'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'gec-subject-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function edit(Subject $subject): RedirectResponse
    {
        $this->ensureMinorSubject($subject);

        return redirect()->route('gec.subjects.index', ['edit' => $subject->id]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureMinorSubject($subject);
        $validated = $this->validated($request, $subject);

        $subject->update($validated);

        return redirect()->route('gec.subjects.index')->with('success', 'Minor subject updated successfully.');
    }

    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureMinorSubject($subject);
        abort_unless(in_array($subject->semester, $this->enabledSemesters($request), true), 404);
        ClassSchedule::withTrashed()->where('subject_id', $subject->id)->forceDelete();
        $subject->instructors()->detach();
        $subject->delete();

        return back()->with('success', 'Minor subject deleted successfully.');
    }

    private function validated(Request $request, ?Subject $subject = null): array
    {
        $request->merge([
            'name' => Str::squish((string) $request->input('name')),
        ]);

        $validated = $request->validate([
            'course' => ['required', Rule::in(self::REAL_DEPARTMENTS)],
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'subject_type' => ['required', Rule::in(['Lecture', 'Laboratory'])],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'curriculum' => ['required', Rule::in(['New', 'Old'])],
            'units' => ['required', 'numeric', 'between:0.5,12'],
        ]);

        $codeAlreadyExists = Subject::query()
            ->where('course', $validated['course'])
            ->where('curriculum', $validated['curriculum'])
            ->where('code', $validated['code'])
            ->when($subject?->exists, fn ($query) => $query->whereKeyNot($subject->id))
            ->exists();

        if ($codeAlreadyExists) {
            throw ValidationException::withMessages([
                'code' => "{$validated['code']} already exists in {$validated['curriculum']} Curriculum for {$validated['course']}. Choose another code or select the other curriculum/department.",
            ]);
        }

        $validated['classification'] = 'Minor';

        return $validated;
    }
}
