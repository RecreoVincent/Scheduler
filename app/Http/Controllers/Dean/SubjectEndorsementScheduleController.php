<?php

namespace App\Http\Controllers\Dean;

use App\Exceptions\ScheduleGenerationException;
use App\Models\AcademicSection;
use App\Models\Room;
use App\Models\SubjectEndorsement;
use App\Models\User;
use App\Services\ClassScheduleGenerator;
use App\Services\ScheduleNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectEndorsementScheduleController extends DeanController
{
    public function __construct(
        private readonly ClassScheduleGenerator $generator,
        private readonly ScheduleNotificationService $notifications,
    ) {}

    public function create(Request $request, SubjectEndorsement $endorsement): View
    {
        $this->ensureRecipient($request, $endorsement);
        $endorsement->load('subject');
        abort_unless($endorsement->subject, 404);

        $sourceDepartment = $endorsement->from_department;
        $sections = AcademicSection::query()
            ->forDepartment($sourceDepartment)
            ->where('year_level', $endorsement->subject->year_level)
            ->orderByDesc('academic_year')
            ->orderBy('name')
            ->get();
        $instructors = $this->activeRecipientInstructors($request);
        $enabledSemesters = $this->enabledSemesters($request);

        return view('dean.subject-endorsements.schedule', compact(
            'endorsement',
            'sourceDepartment',
            'sections',
            'instructors',
            'enabledSemesters',
        ));
    }

    public function store(Request $request, SubjectEndorsement $endorsement): RedirectResponse
    {
        $this->ensureRecipient($request, $endorsement);
        $endorsement->load('subject');
        abort_unless($endorsement->subject, 404);

        $validated = $request->validate([
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', Rule::in($this->enabledSemesters($request))],
            'number_of_sections' => ['required', 'integer', 'between:1,20'],
            'instructor_ids' => ['required', 'array', 'min:1', 'max:4'],
            'instructor_ids.*' => ['nullable', 'integer'],
        ]);
        $instructorIds = collect($validated['instructor_ids'])
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($instructorIds->isEmpty()) {
            throw ValidationException::withMessages(['instructor_ids' => 'Select at least one instructor.']);
        }
        if ($instructorIds->unique()->count() !== $instructorIds->count()) {
            throw ValidationException::withMessages(['instructor_ids' => 'Each instructor can only be selected once.']);
        }

        $instructorsById = $this->activeRecipientInstructors($request)->keyBy('id');
        $instructors = $instructorIds
            ->map(fn (int $id) => $instructorsById->get($id))
            ->filter()
            ->values();
        abort_unless($instructors->count() === $instructorIds->count(), 422);

        $sourceDepartment = $endorsement->from_department;
        $availableSections = AcademicSection::query()
            ->forDepartment($sourceDepartment)
            ->where('academic_year', $validated['academic_year'])
            ->where('year_level', $endorsement->subject->year_level)
            ->orderBy('name')
            ->get();

        if ($availableSections->count() < $validated['number_of_sections']) {
            throw ValidationException::withMessages([
                'number_of_sections' => "Only {$availableSections->count()} {$sourceDepartment} Year {$endorsement->subject->year_level} section(s) are available for the selected academic year.",
            ]);
        }

        $sections = $availableSections->take($validated['number_of_sections'])->values();
        $subject = $endorsement->subject->setRelation('instructors', $instructors);
        $rooms = Room::query()->forDepartment($this->course($request))->orderBy('name')->get();

        try {
            $created = $this->generator->generate(
                $sourceDepartment,
                $sections,
                collect([$subject]),
                $rooms,
                $instructors,
                $validated,
                partialSubjectGeneration: true,
            );
        } catch (ScheduleGenerationException $exception) {
            return back()->withInput()->with('error', $exception->getMessage().' No schedules were changed.')->with('error_note', $exception->guidance);
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage().' No schedules were changed.');
        }

        $endorsement->update([
            'scheduled_by' => $request->user()->id,
            'scheduled_at' => now(),
        ]);
        $this->notifications->schedulesGenerated($sections, $validated);

        return redirect()
            ->route('dean.subject-endorsements.schedule.create', $endorsement)
            ->with('success', "{$created} endorsed-subject ".str('schedule')->plural($created).' generated successfully.');
    }

    private function ensureRecipient(Request $request, SubjectEndorsement $endorsement): void
    {
        abort_unless($endorsement->to_department === $this->course($request), 404);
    }

    /** @return Collection<int, User> */
    private function activeRecipientInstructors(Request $request): Collection
    {
        return User::query()
            ->forDepartment($this->course($request))
            ->where('role', 'instructor')
            ->where('account_status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }
}
