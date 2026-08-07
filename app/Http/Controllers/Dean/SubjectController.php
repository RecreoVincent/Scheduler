<?php

namespace App\Http\Controllers\Dean;

use App\Models\ClassSchedule;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $query = Subject::with('instructors')->forDepartment($course);
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

        return view('dean.subjects.index', compact('course', 'subjectsByYear'));
    }

    public function create(Request $request): View
    {
        return $this->formView($request, new Subject);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Subject::create(['course' => $this->course($request), ...$validated]);

        return redirect()->route('dean.subjects.create')->with('success', 'Subject added successfully.');
    }

    public function edit(Request $request, Subject $subject): View
    {
        $this->ensureCourse($request, $subject);

        return $this->formView($request, $subject);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureCourse($request, $subject);
        $validated = $this->validated($request, $subject);

        $subject->update($validated);

        return redirect()->route('dean.subjects.index')->with('success', 'Subject updated successfully.');
    }

    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureCourse($request, $subject);
        ClassSchedule::withTrashed()->where('subject_id', $subject->id)->forceDelete();
        $subject->instructors()->detach();
        $subject->delete();

        return back()->with('success', 'Subject deleted successfully.');
    }

    private function formView(Request $request, Subject $subject): View
    {
        $course = $this->course($request);

        return view('dean.subjects.form', compact('course', 'subject'));
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
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
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
