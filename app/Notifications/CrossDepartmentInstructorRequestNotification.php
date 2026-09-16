<?php

namespace App\Notifications;

use App\Models\CrossDepartmentInstructorRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CrossDepartmentInstructorRequestNotification extends Notification
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

        return [
            'action' => 'cross_department_instructor_requested',
            'title' => 'Instructor request received',
            'message' => "{$this->request->requesting_department} requests a {$this->request->requested_department} instructor for {$subject?->code} — {$subject?->name}.",
            'schedule_id' => null,
            'section_id' => null,
            'url' => route('dean.instructor-requests.index'),
        ];
    }
}
