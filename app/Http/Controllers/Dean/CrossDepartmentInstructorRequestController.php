<?php

namespace App\Http\Controllers\Dean;

use App\Models\CrossDepartmentInstructorRequest;
use App\Models\User;
use App\Notifications\CrossDepartmentInstructorAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CrossDepartmentInstructorRequestController extends DeanController
{
    public function index(Request $request): View
    {
        $course = $this->course($request);
        $incoming = CrossDepartmentInstructorRequest::query()
            ->with(['subject', 'requestedBy', 'assignedInstructors'])
            ->where('requested_department', $course)
            ->latest()
            ->get();
        $outgoing = CrossDepartmentInstructorRequest::query()
            ->with(['subject', 'assignedInstructors'])
            ->where('requesting_department', $course)
            ->latest()
            ->get();
        $instructors = User::query()
            ->forDepartment($course)
            ->where('role', 'instructor')
            ->where('account_status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('dean.instructor-requests.index', compact('course', 'incoming', 'outgoing', 'instructors'));
    }

    public function fulfill(Request $request, CrossDepartmentInstructorRequest $instructorRequest): RedirectResponse
    {
        abort_unless(
            $instructorRequest->requested_department === $this->course($request)
                && $instructorRequest->status === 'pending',
            404,
        );

        $validated = $request->validate([
            'instructor_ids' => ['required', 'array', 'min:1', 'max:4'],
            'instructor_ids.*' => ['nullable', 'integer'],
        ]);
        $priorityInstructorIds = collect($validated['instructor_ids'])
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($priorityInstructorIds->isEmpty()) {
            throw ValidationException::withMessages([
                'instructor_ids' => 'Select at least one Priority 1 instructor.',
            ]);
        }

        if ($priorityInstructorIds->unique()->count() !== $priorityInstructorIds->count()) {
            throw ValidationException::withMessages([
                'instructor_ids' => 'Each instructor can only be selected once in the priority list.',
            ]);
        }

        $course = $this->course($request);
        $instructorsById = User::query()
            ->whereIn('id', $priorityInstructorIds)
            ->forDepartment($course)
            ->where('role', 'instructor')
            ->where('account_status', 'active')
            ->get()
            ->keyBy('id');
        $instructors = $priorityInstructorIds
            ->map(fn (int $id) => $instructorsById->get($id))
            ->filter()
            ->values();

        abort_unless($instructors->count() === $priorityInstructorIds->count(), 422);

        DB::transaction(function () use ($instructorRequest, $instructors, $request): void {
            $instructorRequest->refresh();
            abort_unless($instructorRequest->status === 'pending', 404);
            $subject = $instructorRequest->subject()->lockForUpdate()->firstOrFail();
            $existingPriorities = $subject->instructors()
                ->pluck('subject_instructor.priority', 'users.id')
                ->mapWithKeys(fn ($priority, $id): array => [(int) $id => (int) $priority]);
            $newInstructorCount = $instructors
                ->reject(fn (User $instructor): bool => $existingPriorities->has($instructor->id))
                ->count();
            $remainingPrioritySlots = 4 - $existingPriorities->count();

            if ($newInstructorCount > $remainingPrioritySlots) {
                throw ValidationException::withMessages([
                    'instructor_ids' => "This subject has {$remainingPrioritySlots} instructor priority ".str('slot')->plural($remainingPrioritySlots)." remaining. Remove an existing priority before assigning more instructors.",
                ]);
            }

            $nextPriority = ((int) $existingPriorities->max()) + 1;
            $subjectPriorities = [];
            $requestPriorities = [];
            foreach ($instructors as $index => $instructor) {
                $subjectPriorities[$instructor->id] = [
                    'priority' => $existingPriorities[$instructor->id] ?? $nextPriority++,
                ];
                $requestPriorities[$instructor->id] = ['priority' => $index + 1];
            }

            $subject->instructors()->syncWithoutDetaching($subjectPriorities);
            $instructorRequest->assignedInstructors()->sync($requestPriorities);
            $instructorRequest->update([
                'status' => 'fulfilled',
                'assigned_instructor_id' => $instructors->first()->id,
                'fulfilled_by' => $request->user()->id,
                'fulfilled_at' => now(),
            ]);
        });

        $instructorRequest->load(['subject', 'assignedInstructors', 'requestedBy']);
        if ($instructorRequest->requestedBy) {
            Notification::send($instructorRequest->requestedBy, new CrossDepartmentInstructorAssignedNotification($instructorRequest));
        }

        $instructorNames = $instructors->pluck('name')->join(', ');
        $instructorCount = $instructors->count();

        return back()->with('success', "{$instructorCount} ".str('instructor')->plural($instructorCount)." assigned to {$instructorRequest->subject->code}: {$instructorNames}.");
    }
}
