<?php

namespace App\Http\Controllers\Dean;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SectionController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $query = AcademicSection::where('course', $course);
        foreach (['year_level', 'academic_year'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $sections = $query->orderBy('year_level')->orderBy('name')->paginate(15)->withQueryString();

        return view('dean.sections.index', compact('course', 'sections'));
    }

    public function create(Request $request): View
    {
        return view('dean.sections.form', ['section' => new AcademicSection, 'course' => $this->course($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        AcademicSection::create([
            'course' => $this->course($request),
            'semester' => 'All',
            ...$this->validated($request),
        ]);

        return redirect()->route('dean.sections.create')->with('success', 'Section added successfully.');
    }

    public function edit(Request $request, AcademicSection $section): View
    {
        $this->ensureCourse($request, $section);

        return view('dean.sections.form', ['section' => $section, 'course' => $this->course($request)]);
    }

    public function update(Request $request, AcademicSection $section): RedirectResponse
    {
        $this->ensureCourse($request, $section);
        $section->update($this->validated($request, $section));

        return redirect()->route('dean.sections.index')->with('success', 'Section updated successfully.');
    }

    public function destroy(Request $request, AcademicSection $section): RedirectResponse
    {
        $this->ensureCourse($request, $section);
        ClassSchedule::withTrashed()->where('section_id', $section->id)->forceDelete();
        $section->delete();

        return back()->with('success', 'Section deleted successfully.');
    }

    private function validated(Request $request, ?AcademicSection $section = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('academic_sections')->where(fn ($q) => $q->where('course', $this->course($request))->where('academic_year', $request->academic_year))->ignore($section?->id)],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
        ]);
    }
}
