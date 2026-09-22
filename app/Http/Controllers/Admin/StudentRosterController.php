<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentRoster;
use App\Models\User;
use App\Services\StudentRosterImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.student-roster.index', compact('roster', 'statistics', 'search', 'registeredStudentIds'));
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
}
