<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class MajorSchedulesSentNotification extends Notification
{
    use Queueable;

    /** @param Collection<int, array{academic_year:string, semester:string}> $periods */
    public function __construct(
        private readonly string $course,
        private readonly string $senderName,
        private readonly Collection $periods,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string|null> */
    public function toArray(object $notifiable): array
    {
        $periodLabel = $this->periods
            ->map(fn (array $period): string => "{$period['semester']} Semester {$period['academic_year']}")
            ->unique()
            ->join(', ');

        return [
            'action' => 'major_schedules_sent',
            'title' => 'Major-subject schedules received',
            'message' => "{$this->course} ({$this->senderName}) sent their Major-subject schedules ({$periodLabel}). Use them as a reference while creating Minor-subject schedules.",
            'schedule_id' => null,
            'section_id' => null,
            'url' => route('gec.schedules.create', ['department' => $this->course]),
        ];
    }
}
