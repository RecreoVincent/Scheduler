<?php

namespace Tests\Feature;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InstructorPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_instructor_can_view_dashboard_metrics_from_only_their_schedules(): void
    {
        [$instructor, $section, $subject, $room] = $this->scheduleFixtures();
        $otherInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);

        $this->createSchedule($instructor, $section, $subject, $room, 'Monday', '08:00', '09:30');
        $this->createSchedule($instructor, $section, $subject, $room, 'Wednesday', '08:00', '09:30');
        $this->createSchedule($otherInstructor, $section, $subject, $room, 'Friday', '08:00', '10:00');

        $this->actingAs($instructor)->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('Assigned Sections')
            ->assertDontSee('Scheduled Classes')
            ->assertViewHas('statistics', fn (array $statistics): bool => $statistics === [
                'sections' => 1,
                'subjects' => 1,
                'hours' => '3',
            ]);
    }

    public function test_instructor_workload_only_contains_their_assignments(): void
    {
        [$instructor, $section, $subject, $room] = $this->scheduleFixtures();
        $otherInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $this->createSchedule($instructor, $section, $subject, $room, 'Tuesday', '10:00', '11:00');

        $otherSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'IT999', 'name' => 'Hidden Assignment', 'subject_type' => 'Lecture',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $this->createSchedule($otherInstructor, $section, $otherSubject, $room, 'Thursday', '10:00', '11:00');

        $this->actingAs($instructor)->get(route('instructor.workload.index'))
            ->assertOk()
            ->assertSee($subject->code)
            ->assertDontSee('IT999');
    }

    public function test_room_scanner_reports_a_room_currently_in_use(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));
        [$instructor, $section, $subject, $room] = $this->scheduleFixtures();
        $this->createSchedule($instructor, $section, $subject, $room, 'Monday', '08:30', '10:00');

        $this->actingAs($instructor)->getJson(route('instructor.scanner.status', $room))
            ->assertOk()
            ->assertJsonPath('in_use', true)
            ->assertJsonPath('current.section', $section->name)
            ->assertJsonPath('room.name', $room->name);

        Carbon::setTestNow();
    }

    public function test_instructor_can_update_their_personal_information(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);

        $this->actingAs($instructor)->patch(route('instructor.profile.update'), [
            'first_name' => 'Updated',
            'middle_name' => 'Middle',
            'last_name' => 'Instructor',
            'suffix' => 'Jr.',
            'email' => 'updated.instructor@example.com',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $instructor->id,
            'first_name' => 'Updated',
            'last_name' => 'Instructor',
            'email' => 'updated.instructor@example.com',
            'role' => 'instructor',
            'course' => 'BSIT',
        ]);
    }

    public function test_instructor_can_open_printable_workload(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);

        $this->actingAs($instructor)->get(route('instructor.print.workload'))
            ->assertOk()
            ->assertSee('Instructor Teaching Workload')
            ->assertSee($instructor->name);
    }

    public function test_non_instructor_and_pending_instructor_cannot_access_portal(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT', 'account_status' => 'active']);
        $pending = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'pending']);

        $this->actingAs($dean)->get(route('instructor.dashboard'))->assertForbidden();
        $this->actingAs($pending)->get(route('instructor.dashboard'))->assertForbidden();
    }

    /** @return array{User, AcademicSection, Subject, Room} */
    private function scheduleFixtures(): array
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => 'North', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'IT101', 'name' => 'Introduction to Computing',
            'subject_type' => 'Lecture', 'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Lab 1']);

        return [$instructor, $section, $subject, $room];
    }

    private function createSchedule(User $instructor, AcademicSection $section, Subject $subject, Room $room, string $day, string $start, string $end): ClassSchedule
    {
        return ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => $day,
            'start_time' => $start, 'end_time' => $end,
        ]);
    }
}
