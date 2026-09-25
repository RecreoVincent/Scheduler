<?php

namespace App\Http\Controllers\Dean;

use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Subject;
use App\Models\SubjectEndorsement;
use App\Models\User;
use App\Notifications\SubjectEndorsementReceivedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
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
        $endorsementSubjects = Subject::query()
            ->forDepartment($course)
            ->where('managed_by_gec', false)
            ->whereNotNull('year_level')
            ->whereIn('semester', $this->enabledSemesters($request))
            ->orderBy('year_level')
            ->orderBy('code')
            ->orderBy('name')
            ->get(['id', 'course', 'code', 'name', 'subject_type', 'year_level', 'units']);
        $endorsements = SubjectEndorsement::query()
            ->where('from_department', $course)
            ->whereNull('scheduled_at')
            ->latest()
            ->get();
        $receivedEndorsements = SubjectEndorsement::query()
            ->with('subject')
            ->where('to_department', $course)
            ->whereNull('scheduled_at')
            ->latest()
            ->get();
        $historyArchiveSupported = Schema::hasColumns('subject_endorsements', [
            'from_department_archived_at',
            'to_department_archived_at',
        ]);
        $endorsementHistoryQuery = SubjectEndorsement::query()
            ->with(['subject', 'scheduledBy'])
            ->whereNotNull('scheduled_at');
        if ($historyArchiveSupported) {
            $endorsementHistoryQuery->where(function ($query) use ($course): void {
                $query
                    ->where(fn ($sentQuery) => $sentQuery
                        ->where('from_department', $course)
                        ->whereNull('from_department_archived_at'))
                    ->orWhere(fn ($receivedQuery) => $receivedQuery
                        ->where('to_department', $course)
                        ->whereNull('to_department_archived_at'));
            });
        } else {
            // Allow a rolling deployment to keep the page available until the
            // new history-archive migration is applied on the server.
            $endorsementHistoryQuery->where(fn ($query) => $query
                ->where('from_department', $course)
                ->orWhere('to_department', $course));
        }
        $endorsementHistory = $endorsementHistoryQuery
            ->latest('scheduled_at')
            ->get();

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

        return view('dean.subject-endorsements.index', compact(
            'course',
            'departments',
            'endorsementSubjects',
            'endorsements',
            'receivedEndorsements',
            'endorsementHistory',
            'historyArchiveSupported',
        ));
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
            'year_level' => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'subject_id' => ['required', 'integer'],
        ]);

        $subject = Subject::query()
            ->forDepartment($course)
            ->where('managed_by_gec', false)
            ->whereKey($validated['subject_id'])
            ->where('year_level', $validated['year_level'])
            ->whereIn('semester', $this->enabledSemesters($request))
            ->first();

        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_id' => "Select a subject from the {$course} Subjects list for the chosen year level.",
            ]);
        }
        $subjectCode = $subject->code;

        $endorsement = SubjectEndorsement::create([
            'subject_id' => $subject->id,
            'from_department' => $course,
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
            ->route('dean.subject-endorsements.index')
            ->with('success', "{$subjectCode} was endorsed to {$validated['to_department']} successfully. {$deliveryMessage}");
    }

    public function clearHistory(Request $request): RedirectResponse
    {
        if (! Schema::hasColumns('subject_endorsements', ['from_department_archived_at', 'to_department_archived_at'])) {
            return back()->with('error', 'The history update is still being installed. Run the latest database migration, then try again.');
        }

        $course = $this->course($request);
        $archivedAt = now();
        $sentArchivedCount = SubjectEndorsement::query()
            ->where('from_department', $course)
            ->whereNotNull('scheduled_at')
            ->whereNull('from_department_archived_at')
            ->update(['from_department_archived_at' => $archivedAt]);
        $receivedArchivedCount = SubjectEndorsement::query()
            ->where('to_department', $course)
            ->whereNotNull('scheduled_at')
            ->whereNull('to_department_archived_at')
            ->update(['to_department_archived_at' => $archivedAt]);
        $archivedCount = $sentArchivedCount + $receivedArchivedCount;

        if ($archivedCount === 0) {
            return back()->with('error', 'There is no completed endorsement history to delete.');
        }

        return back()->with('success', "{$archivedCount} completed ".str('endorsement')->plural($archivedCount).' removed from this department\'s history. Generated schedules and the other department\'s history are unchanged.');
    }
}
