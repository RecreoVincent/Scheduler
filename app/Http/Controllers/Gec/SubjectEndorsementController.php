<?php

namespace App\Http\Controllers\Gec;

use App\Models\ClassSchedule;
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

class SubjectEndorsementController extends GecController
{
    public function index(Request $request): View
    {
        $departments = Department::query()
            ->whereIn('code', self::REAL_DEPARTMENTS)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['code', 'name', 'program_name']);
        $endorsements = SubjectEndorsement::query()
            ->with(['subject', 'scheduledBy'])
            ->where('endorsed_by', $request->user()->id)
            ->latest()
            ->get();
        $endorsementHistory = $endorsements->filter(fn (SubjectEndorsement $endorsement): bool => $endorsement->scheduled_at !== null)->values();
        $pendingEndorsements = $endorsements->filter(fn (SubjectEndorsement $endorsement): bool => $endorsement->scheduled_at === null)->values();

        $historySchedules = $endorsementHistory->isEmpty()
            ? collect()
            : ClassSchedule::query()
                ->with(['section', 'instructor', 'room'])
                ->whereIn('subject_id', $endorsementHistory->pluck('subject_id')->unique()->all())
                ->whereIn('course', $endorsementHistory->pluck('from_department')->unique()->all())
                ->orderBy('academic_year')
                ->orderBy('semester')
                ->orderByRaw(ClassSchedule::dayOrderSql())
                ->orderBy('start_time')
                ->get();
        $endorsementHistory->each(function (SubjectEndorsement $endorsement) use ($historySchedules): void {
            $endorsement->setRelation('scheduledClasses', $historySchedules
                ->where('subject_id', $endorsement->subject_id)
                ->where('course', $endorsement->from_department)
                ->values());
        });

        return view('gec.subject-endorsements.index', compact(
            'departments',
            'pendingEndorsements',
            'endorsementHistory',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_department' => ['required', Rule::in(self::REAL_DEPARTMENTS)],
            'to_department' => ['required', Rule::in(self::REAL_DEPARTMENTS), 'different:from_department'],
            'subject_code' => ['required', 'string', 'max:30'],
            'subject_name' => ['required', 'string', 'max:150'],
            'subject_type' => ['required', Rule::in(['Lecture', 'Laboratory'])],
            'units' => ['required', 'numeric', 'between:0.5,12'],
        ]);

        $subjectCode = strtoupper(trim($validated['subject_code']));
        $subjectName = trim($validated['subject_name']);
        $subject = Subject::query()
            ->forDepartment($validated['from_department'])
            ->where('managed_by_gec', true)
            ->where('classification', 'Minor')
            ->where('code', $subjectCode)
            ->where('subject_type', $validated['subject_type'])
            ->where('units', $validated['units'])
            ->latest('id')
            ->first();

        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_code' => "Add {$subjectCode} to the {$validated['from_department']} Minor Subjects list first, using the same subject type and units, before endorsing it.",
            ]);
        }
        if (strcasecmp($subject->name, $subjectName) !== 0) {
            throw ValidationException::withMessages([
                'subject_name' => "The subject name must match {$subjectCode} in the {$validated['from_department']} Minor Subjects list.",
            ]);
        }

        $endorsement = SubjectEndorsement::create([
            'subject_id' => $subject->id,
            'from_department' => $validated['from_department'],
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
            ->route('gec.subject-endorsements.index')
            ->with('success', "{$subjectCode} was endorsed to {$validated['to_department']} successfully. {$deliveryMessage}");
    }
}
