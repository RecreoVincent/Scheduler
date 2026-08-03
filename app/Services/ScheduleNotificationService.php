<?php

namespace App\Services;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\User;
use App\Notifications\ScheduleChangedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class ScheduleNotificationService
{
    public function scheduleUpdated(ClassSchedule $schedule, ?int $previousInstructorId = null): void
    {
        $schedule->loadMissing(['section', 'subject', 'room']);
        $instructorIds = collect([$schedule->instructor_id, $previousInstructorId])
            ->filter()
            ->unique()
            ->values();

        $this->send(
            collect([$schedule->section_id]),
            $instructorIds,
            [
                'action' => 'updated',
                'title' => 'Class schedule updated',
                'message' => $this->scheduleDescription($schedule).' was updated by the dean.',
                'schedule_id' => $schedule->id,
                'section_id' => $schedule->section_id,
            ],
        );
    }

    public function scheduleArchived(ClassSchedule $schedule): void
    {
        $schedule->loadMissing(['section', 'subject', 'room']);

        $this->send(
            collect([$schedule->section_id]),
            collect([$schedule->instructor_id]),
            [
                'action' => 'archived',
                'title' => 'Class schedule removed',
                'message' => $this->scheduleDescription($schedule).' was removed from the active timetable by the dean.',
                'schedule_id' => $schedule->id,
                'section_id' => $schedule->section_id,
            ],
        );
    }

    /** @param Collection<int, ClassSchedule> $schedules */
    public function sectionArchived(AcademicSection $section, Collection $schedules): void
    {
        if ($schedules->isEmpty()) {
            return;
        }

        $this->send(
            collect([$section->id]),
            $schedules->pluck('instructor_id')->filter()->unique()->values(),
            [
                'action' => 'section_archived',
                'title' => 'Section schedule removed',
                'message' => "The dean removed the complete {$section->name} timetable ({$schedules->count()} class ".str('entry')->plural($schedules->count()).').',
                'schedule_id' => null,
                'section_id' => $section->id,
            ],
        );
    }

    /** @param Collection<int, ClassSchedule> $schedules */
    public function schedulesArchived(Collection $schedules): void
    {
        if ($schedules->isEmpty()) {
            return;
        }

        $sectionIds = $schedules->pluck('section_id')->filter()->unique()->values();

        $this->send(
            $sectionIds,
            $schedules->pluck('instructor_id')->filter()->unique()->values(),
            [
                'action' => 'schedules_archived',
                'title' => 'Schedules removed',
                'message' => "The dean moved {$schedules->count()} class ".str('entry')->plural($schedules->count()).' from the active timetable to the archive.',
                'schedule_id' => null,
                'section_id' => $sectionIds->count() === 1 ? $sectionIds->first() : null,
            ],
        );
    }

    public function scheduleRestored(ClassSchedule $schedule): void
    {
        $schedule->loadMissing(['section', 'subject', 'room']);

        $this->send(
            collect([$schedule->section_id]),
            collect([$schedule->instructor_id]),
            [
                'action' => 'restored',
                'title' => 'Class schedule restored',
                'message' => $this->scheduleDescription($schedule).' was restored to the active timetable by the dean.',
                'schedule_id' => $schedule->id,
                'section_id' => $schedule->section_id,
            ],
        );
    }

    /**
     * @param  Collection<int, AcademicSection>  $sections
     * @param  array{academic_year:string, semester:string}  $period
     */
    public function schedulesGenerated(Collection $sections, array $period): void
    {
        $sectionIds = $sections->pluck('id')->filter()->values();
        $schedules = ClassSchedule::query()
            ->whereIn('section_id', $sectionIds)
            ->where('academic_year', $period['academic_year'])
            ->where('semester', $period['semester'])
            ->get();

        if ($schedules->isEmpty()) {
            return;
        }

        $this->send(
            $sectionIds,
            $schedules->pluck('instructor_id')->filter()->unique()->values(),
            [
                'action' => 'generated',
                'title' => 'New class schedule available',
                'message' => "The dean published the {$period['semester']} Semester, {$period['academic_year']} timetable. Open your schedule to review the latest assignments.",
                'schedule_id' => null,
                'section_id' => $sectionIds->count() === 1 ? $sectionIds->first() : null,
            ],
        );
    }

    /**
     * @param  Collection<int, int>  $sectionIds
     * @param  Collection<int, int>  $instructorIds
     * @param  array<string, int|string|null>  $details
     */
    private function send(Collection $sectionIds, Collection $instructorIds, array $details): void
    {
        $instructors = User::query()
            ->where('role', 'instructor')
            ->where('account_status', 'active')
            ->whereIn('id', $instructorIds)
            ->get();
        $students = User::query()
            ->where('role', 'student')
            ->where('account_status', 'active')
            ->whereIn('academic_section_id', $sectionIds)
            ->get();
        $recipients = $instructors->concat($students)->unique('id')->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ScheduleChangedNotification($details));
        }
    }

    private function scheduleDescription(ClassSchedule $schedule): string
    {
        $subject = $schedule->subject?->code ?? 'Class';
        $section = $schedule->section?->name ?? 'the assigned section';
        $room = $schedule->room?->name ?? 'TBA';
        $start = date('g:i A', strtotime($schedule->start_time));
        $end = date('g:i A', strtotime($schedule->end_time));

        return "{$subject} for {$section} ({$schedule->day}, {$start}–{$end}, {$room})";
    }
}
