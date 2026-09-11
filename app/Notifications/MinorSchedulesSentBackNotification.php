<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class MinorSchedulesSentBackNotification extends Notification
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
            'action' => 'minor_schedules_sent_back',
            'title' => 'Minor-subject schedules received',
            'message' => "GEC ({$this->senderName}) sent back the completed Minor-subject schedules for {$this->course} ({$periodLabel}).",
            'schedule_id' => null,
            'section_id' => null,
            'url' => route('dean.timetable.index'),
        ];
    }
}
