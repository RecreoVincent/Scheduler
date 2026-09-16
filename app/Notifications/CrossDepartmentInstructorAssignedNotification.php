<?php

namespace App\Notifications;

use App\Models\CrossDepartmentInstructorRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CrossDepartmentInstructorAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly CrossDepartmentInstructorRequest $request) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string|null> */
    public function toArray(object $notifiable): array
    {
        $subject = $this->request->subject;
        $instructorNames = $this->request->assignedInstructors->pluck('name')->join(', ')
            ?: $this->request->assignedInstructor?->name;
        $verb = $this->request->assignedInstructors->count() === 1 ? 'was' : 'were';

        return [
            'action' => 'cross_department_instructor_assigned',
            'title' => 'Requested instructor assigned',
            'message' => "{$instructorNames} from {$this->request->requested_department} {$verb} assigned to {$subject?->code}.",
            'schedule_id' => null,
            'section_id' => null,
            'url' => route('dean.subject-assignments.index'),
        ];
    }
}
