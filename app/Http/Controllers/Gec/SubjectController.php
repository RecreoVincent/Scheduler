<?php

namespace App\Http\Controllers\Gec;

use App\Models\ClassSchedule;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
            ->paginate(15)
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

        // Subjects GEC originates itself stay exclusive to the GEC portal —
        // the owning department's own Subjects page must not show or manage
        // them. Subjects already created by a department's own Dean (before
        // GEC existed, or independently of it) are untouched by this flag
        // and keep showing on that Dean's page as before.
        Subject::create([...$validated, 'managed_by_gec' => true]);

        return redirect()->route('gec.subjects.index')->with('success', 'Minor subject added successfully.');
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

    public function destroy(Subject $subject): RedirectResponse
    {
        $this->ensureMinorSubject($subject);
        ClassSchedule::withTrashed()->where('subject_id', $subject->id)->forceDelete();
        $subject->instructors()->detach();
        $subject->delete();

        return back()->with('success', 'Minor subject deleted successfully.');
    }

    private function validated(Request $request, ?Subject $subject = null): array
    {
        $validated = $request->validate([
            'course' => ['required', Rule::in(self::REAL_DEPARTMENTS)],
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:150'],
            'subject_type' => ['required', Rule::in(['Lecture', 'Laboratory'])],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
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
