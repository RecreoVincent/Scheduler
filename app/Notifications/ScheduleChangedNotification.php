<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScheduleChangedNotification extends Notification
{
    use Queueable;

    /** @param array<string, int|string|null> $details */
    public function __construct(private readonly array $details) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string|null> */
    public function toArray(object $notifiable): array
    {
        $destination = $notifiable->role === 'student'
            ? route('student.study-load.index')
            : route('instructor.workload.index');

        return [...$this->details, 'url' => $destination];
    }
}
