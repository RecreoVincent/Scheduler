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
        $endorsementSubjects = $this->minorSubjects()
            ->where('managed_by_gec', true)
            ->whereNotNull('year_level')
            ->whereIn('semester', $this->enabledSemesters($request))
            ->orderBy('course')
            ->orderBy('year_level')
            ->orderBy('code')
            ->orderBy('name')
            ->get(['id', 'course', 'code', 'name', 'subject_type', 'year_level', 'units']);
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
            'endorsementSubjects',
            'pendingEndorsements',
            'endorsementHistory',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_department' => ['required', Rule::in(self::REAL_DEPARTMENTS)],
            'to_department' => ['required', Rule::in(self::REAL_DEPARTMENTS), 'different:from_department'],
            'year_level' => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'subject_id' => ['required', 'integer'],
        ]);

        $subject = Subject::query()
            ->forDepartment($validated['from_department'])
            ->where('managed_by_gec', true)
            ->where('classification', 'Minor')
            ->whereKey($validated['subject_id'])
            ->where('year_level', $validated['year_level'])
            ->whereIn('semester', $this->enabledSemesters($request))
            ->first();

        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_id' => "Select a Minor subject from the {$validated['from_department']} list for the chosen year level.",
            ]);
        }
        $subjectCode = $subject->code;

        $endorsement = SubjectEndorsement::create([
            'subject_id' => $subject->id,
            'from_department' => $validated['from_department'],
            'to_department' => $validated['to_department'],
            'subject_code' => $subjectCode,
            'subject_name' => $subject->name,
            'subject_type' => $subject->subject_type,
            'units' => $subject->units,
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
