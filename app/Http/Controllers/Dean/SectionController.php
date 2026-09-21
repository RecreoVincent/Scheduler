<?php

namespace App\Http\Controllers\Dean;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Services\SectionImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SectionController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $query = AcademicSection::forDepartment($course);
        foreach (['year_level', 'academic_year'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $sections = $query->orderBy('year_level')->orderBy('name')->paginate(15)->withQueryString();

        $editingSection = null;
        if ($request->filled('edit')) {
            $editingSection = AcademicSection::forDepartment($course)->find($request->input('edit'));
        }

        return view('dean.sections.index', compact('course', 'sections', 'editingSection'));
    }

    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('dean.sections.index');
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

    public function import(Request $request, SectionImporter $importer): RedirectResponse
    {
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        try {
            $result = $importer->import($request->file('csv_file')->getRealPath(), $this->course($request));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $response = back()->with('success', "Import complete: {$result['imported']} section(s) created, {$result['skipped']} skipped.");

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
        $headers = ['name', 'year_level', 'academic_year'];
        $sample = ['1 - East', '1', '2026-2027'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'section-import-template.csv', ['Content-Type' => 'text/csv']);
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

    public function destroyAll(Request $request): RedirectResponse
    {
        $course = $this->course($request);
        $sectionIds = AcademicSection::query()->forDepartment($course)->pluck('id');

        if ($sectionIds->isEmpty()) {
            return back()->with('error', "There are no {$course} sections to remove.");
        }

        DB::transaction(function () use ($sectionIds): void {
            ClassSchedule::withTrashed()->whereIn('section_id', $sectionIds)->forceDelete();
            AcademicSection::query()->whereIn('id', $sectionIds)->delete();
        });

        $count = $sectionIds->count();

        return redirect()
            ->route('dean.sections.index')
            ->with('success', "Removed all {$count} {$course} ".str('section')->plural($count).' and their schedules.');
    }

    private function validated(Request $request, ?AcademicSection $section = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('academic_sections')->where(fn ($q) => $q->where('department_id', $request->user()->department_id)->where('academic_year', $request->academic_year))->ignore($section?->id)],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
        ]);
    }
}
