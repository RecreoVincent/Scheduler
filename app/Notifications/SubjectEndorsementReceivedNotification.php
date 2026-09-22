<?php

namespace App\Notifications;

use App\Models\SubjectEndorsement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubjectEndorsementReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SubjectEndorsement $endorsement) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string|null> */
    public function toArray(object $notifiable): array
    {
        return [
            'action' => 'subject_endorsement_received',
            'title' => 'Subject endorsement received',
            'message' => "{$this->endorsement->from_department} endorsed {$this->endorsement->subject_code} — {$this->endorsement->subject_name} ({$this->endorsement->subject_type}, {$this->endorsement->units} units) to {$this->endorsement->to_department}. Open it to create the endorsed subject schedule.",
            'schedule_id' => null,
            'section_id' => null,
            'url' => route('dean.subject-endorsements.schedule.create', $this->endorsement),
        ];
    }
}
