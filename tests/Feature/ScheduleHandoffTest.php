<?php

namespace Tests\Feature;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\ScheduleHandoff;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleHandoffTest extends TestCase
{
    use RefreshDatabase;

    public function test_dean_can_send_major_schedules_to_gec_and_gec_is_notified(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT', 'account_status' => 'active']);
        $gec = User::factory()->create(['role' => 'gec', 'course' => 'GEC', 'account_status' => 'active']);
        $otherDean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA', 'account_status' => 'active']);
        $this->createMajorSchedule('BSIT');

        $this->actingAs($dean)->post(route('dean.timetable.send-to-gec'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $handoff = ScheduleHandoff::where('course', 'BSIT')->first();
        $this->assertNotNull($handoff);
        $this->assertNotNull($handoff->majors_sent_at);
        $this->assertSame($dean->id, $handoff->majors_sent_by);

        $notification = $gec->fresh()->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('BSIT', $notification->data['message']);

        $this->assertNull($otherDean->fresh()->unreadNotifications()->first());

        $this->actingAs($dean)->get(route('dean.timetable.index'))
            ->assertOk()
            ->assertSee('Sent to GEC on');
    }

    public function test_dean_cannot_send_when_there_are_no_active_major_schedules(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT', 'account_status' => 'active']);

        $this->actingAs($dean)->post(route('dean.timetable.send-to-gec'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('schedule_handoffs', 0);
    }

    public function test_gec_create_schedule_page_shows_major_schedule_handoff_status(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSHM', 'account_status' => 'active']);
        $gec = User::factory()->create(['role' => 'gec', 'course' => 'GEC', 'account_status' => 'active']);
        $this->createMajorSchedule('BSHM');

        $this->actingAs($gec)->get(route('gec.schedules.create', ['department' => 'BSHM']))
            ->assertOk()
            ->assertSee('Major-subject schedules to GEC yet');

        $this->actingAs($dean)->post(route('dean.timetable.send-to-gec'))->assertRedirect();

        $this->actingAs($gec)->get(route('gec.schedules.create', ['department' => 'BSHM']))
            ->assertOk()
            ->assertSee('Major schedules received')
            ->assertDontSee('Major-subject schedules to GEC yet');
    }

    public function test_gec_can_send_minor_schedules_back_to_the_correct_departments_dean(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA', 'account_status' => 'active']);
        $otherDean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT', 'account_status' => 'active']);
        $gec = User::factory()->create(['role' => 'gec', 'course' => 'GEC', 'account_status' => 'active']);
        $this->createMinorSchedule('BSBA');

        $this->actingAs($gec)->post(route('gec.timetable.send-to-dean'), ['department' => 'BSBA'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $handoff = ScheduleHandoff::where('course', 'BSBA')->first();
        $this->assertNotNull($handoff);
        $this->assertNotNull($handoff->minors_sent_back_at);
        $this->assertSame($gec->id, $handoff->minors_sent_back_by);

        $notification = $dean->fresh()->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('BSBA', $notification->data['message']);

        $this->assertNull($otherDean->fresh()->unreadNotifications()->first());
    }

    public function test_regenerating_major_schedules_does_not_delete_existing_minor_schedules(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA', 'account_status' => 'active']);
        [$section, , $instructor] = $this->createMajorSchedule('BSBA', 2);
        [, , $gecInstructor] = $this->createMinorSchedule('BSBA', $section);

        $majorSubject = Subject::create([
            'course' => 'BSBA', 'code' => 'BA201', 'name' => 'Marketing', 'subject_type' => 'Lecture',
            'classification' => 'Major', 'year_level' => $section->year_level, 'semester' => '1st', 'units' => 3,
        ]);
        $majorSubject->instructors()->attach($instructor->id, ['priority' => 1]);

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => $section->academic_year,
            'semester' => '1st',
            'curriculum' => 'New',
            'year_levels' => [(string) $section->year_level],
            'number_of_sections' => 1,
        ]);

        $this->assertDatabaseHas('class_schedules', [
            'section_id' => $section->id,
            'instructor_id' => $gecInstructor->id,
            'deleted_at' => null,
        ]);
    }

    /** @return array{AcademicSection, ClassSchedule, User} */
    private function createMajorSchedule(string $course, int $yearLevel = 1): array
    {
        $section = AcademicSection::create([
            'course' => $course, 'name' => '1 - A', 'year_level' => $yearLevel,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => $course, 'employment_type' => null, 'account_status' => 'active',
        ]);
        $subject = Subject::create([
            'course' => $course, 'code' => strtoupper($course).'101', 'name' => 'Sample Major Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => $yearLevel, 'semester' => '1st', 'units' => 3,
        ]);
        $room = \App\Models\Room::create(['course' => $course, 'name' => 'Room 101', 'room_type' => 'Lecture']);
        $schedule = ClassSchedule::create([
            'course' => $course, 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st',
            'day' => 'M - W', 'start_time' => '07:30', 'end_time' => '10:00',
        ]);

        return [$section, $schedule, $instructor];
    }

    /** @return array{AcademicSection, ClassSchedule, User} */
    private function createMinorSchedule(string $course, ?AcademicSection $section = null): array
    {
        $section ??= AcademicSection::create([
            'course' => $course, 'name' => '1 - A', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $gecInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'GEC', 'employment_type' => null, 'account_status' => 'active',
        ]);
        $minorSubject = Subject::create([
            'course' => $course, 'code' => 'GE101', 'name' => 'Understanding the Self',
            'subject_type' => 'Lecture', 'classification' => 'Minor',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $schedule = ClassSchedule::create([
            'course' => $course, 'section_id' => $section->id, 'subject_id' => $minorSubject->id,
            'instructor_id' => $gecInstructor->id, 'room_id' => null,
            'academic_year' => '2026-2027', 'semester' => '1st',
            'day' => 'T - Th', 'start_time' => '13:00', 'end_time' => '14:30',
        ]);

        return [$section, $schedule, $gecInstructor];
    }
}
