<?php

namespace App\Http\Controllers\Dean;

use App\Models\Department;
use App\Models\Subject;
use App\Models\SubjectEndorsement;
use App\Models\User;
use App\Notifications\SubjectEndorsementReceivedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectEndorsementController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $departments = Department::query()
            ->whereNotIn('code', [$course, 'GEC'])
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['code', 'name', 'program_name']);
        $endorsements = SubjectEndorsement::query()
            ->where('from_department', $course)
            ->latest()
            ->get();
        $receivedEndorsements = SubjectEndorsement::query()
            ->with('subject')
            ->where('to_department', $course)
            ->latest()
            ->get();

        return view('dean.subject-endorsements.index', compact('course', 'departments', 'endorsements', 'receivedEndorsements'));
    }

    public function store(Request $request): RedirectResponse
    {
        $course = $this->course($request);
        $destinationDepartments = Department::query()
            ->whereNotIn('code', [$course, 'GEC'])
            ->pluck('code')
            ->all();
        $validated = $request->validate([
            'to_department' => ['required', Rule::in($destinationDepartments)],
            'subject_code' => ['required', 'string', 'max:30'],
            'subject_name' => ['required', 'string', 'max:150'],
            'subject_type' => ['required', Rule::in(['Lecture', 'Laboratory', 'Internship'])],
            'units' => ['required', 'numeric', 'between:0.5,12'],
        ]);

        $subjectCode = strtoupper(trim($validated['subject_code']));
        $subjectName = trim($validated['subject_name']);
        $subject = Subject::query()
            ->forDepartment($course)
            ->where('managed_by_gec', false)
            ->where('code', $subjectCode)
            ->where('subject_type', $validated['subject_type'])
            ->where('units', $validated['units'])
            ->latest('id')
            ->first();

        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_code' => "Add {$subjectCode} to the {$course} Subjects page first, using the same subject type and units, before endorsing it.",
            ]);
        }
        if (strcasecmp($subject->name, $subjectName) !== 0) {
            throw ValidationException::withMessages([
                'subject_name' => "The subject name must match {$subjectCode} in the {$course} Subjects list.",
            ]);
        }

        $endorsement = SubjectEndorsement::create([
            'subject_id' => $subject->id,
            'from_department' => $course,
            'to_department' => $validated['to_department'],
            'subject_code' => $subjectCode,
            'subject_name' => $subject->name,
            'subject_type' => $validated['subject_type'],
            'units' => $validated['units'],
            'endorsed_by' => $request->user()->id,
        ]);

        $recipients = User::query()
            ->where('role', 'dean')
            ->where('account_status', 'active')
            ->forDepartment($validated['to_department'])
            ->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new SubjectEndorsementReceivedNotification($endorsement));
        }

        $deliveryMessage = $recipients->isNotEmpty()
            ? "The {$validated['to_department']} Dean was notified."
            : "No active {$validated['to_department']} Dean account is available yet, so the endorsement is saved but no notification was delivered.";

        return redirect()
            ->route('dean.subject-endorsements.index')
            ->with('success', "{$subjectCode} was endorsed to {$validated['to_department']} successfully. {$deliveryMessage}");
    }
}
