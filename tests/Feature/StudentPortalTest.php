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

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_and_study_load_only_use_assigned_section(): void
    {
        [$student, $section, $subject, $room, $instructor] = $this->fixtures();
        $this->schedule($section, $subject, $room, $instructor, 'Monday', '08:00', '09:30');
        $otherSection = AcademicSection::create(['course' => 'BSIT', 'name' => 'South', 'year_level' => 1, 'academic_year' => '2026-2027', 'semester' => 'All']);
        $otherSubject = Subject::create(['course' => 'BSIT', 'code' => 'IT999', 'name' => 'Other Section Subject', 'subject_type' => 'Lecture', 'year_level' => 1, 'semester' => '1st', 'units' => 3]);
        $this->schedule($otherSection, $otherSubject, $room, $instructor, 'Tuesday', '08:00', '09:00');

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('Assigned Sections')
            ->assertSee('Student · BSIT · North')
            ->assertSee('View Full Study Load')
            ->assertDontSee('Scheduled Classes')
            ->assertViewHas('statistics', fn (array $statistics): bool => $statistics === [
                'subjects' => 1,
                'units' => '3',
                'hours' => '1.5',
            ]);

        $this->actingAs($student)->get(route('student.study-load.index'))
            ->assertOk()
            ->assertSee('IT101')
            ->assertDontSee('IT999')
            ->assertDontSee('Academic year')
            ->assertDontSee('All semesters')
            ->assertDontSee('Total Units');
    }

    public function test_student_without_section_sees_assignment_notice_and_no_schedule(): void
    {
        $student = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'year_level' => 1, 'academic_section_id' => null, 'account_status' => 'active']);

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('No section assigned')
            ->assertViewHas('statistics', fn (array $statistics): bool => $statistics === [
                'subjects' => 0,
                'units' => '0',
                'hours' => '0',
            ]);
    }

    public function test_student_room_scanner_reports_current_room_usage(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 08:30:00'));
        [$student, $section, $subject, $room, $instructor] = $this->fixtures();
        $this->schedule($section, $subject, $room, $instructor, 'Monday', '08:00', '09:30');

        $this->actingAs($student)->getJson(route('student.scanner.status', $room))
            ->assertOk()
            ->assertJsonPath('in_use', true)
            ->assertJsonPath('current.section', $section->name);

        Carbon::setTestNow();
    }

    public function test_student_can_update_personal_information_without_changing_assignment(): void
    {
        [$student, $section] = $this->fixtures();

        $this->actingAs($student)->patch(route('student.profile.update'), [
            'first_name' => 'Updated', 'middle_name' => null, 'last_name' => 'Student',
            'suffix' => null, 'email' => 'updated.student@example.com',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $student->id, 'first_name' => 'Updated', 'email' => 'updated.student@example.com',
            'course' => 'BSIT', 'year_level' => 1, 'academic_section_id' => $section->id,
        ]);
    }

    public function test_student_can_open_printable_study_load(): void
    {
        [$student] = $this->fixtures();

        $this->actingAs($student)->get(route('student.print.study-load'))
            ->assertOk()->assertSee('Student Study Load')->assertSee($student->name);
    }

    public function test_admin_can_assign_only_a_matching_section_to_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        $student = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'year_level' => 1, 'account_status' => 'active']);
        $section = AcademicSection::create(['course' => 'BSIT', 'name' => 'North', 'year_level' => 1, 'academic_year' => '2026-2027', 'semester' => 'All']);
        $otherSection = AcademicSection::create(['course' => 'BSBA', 'name' => 'BA North', 'year_level' => 1, 'academic_year' => '2026-2027', 'semester' => 'All']);
        $payload = [
            'first_name' => $student->first_name, 'middle_name' => $student->middle_name,
            'last_name' => $student->last_name, 'suffix' => $student->suffix, 'email' => $student->email,
            'role' => 'student', 'course' => 'BSIT', 'year_level' => 1, 'account_status' => 'active',
        ];

        $this->actingAs($admin)->put(route('admin.users.update', $student), [...$payload, 'academic_section_id' => $otherSection->id])
            ->assertSessionHasErrors('academic_section_id');
        $this->actingAs($admin)->put(route('admin.users.update', $student), [...$payload, 'academic_section_id' => $section->id])
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $student->id, 'academic_section_id' => $section->id]);
    }

    public function test_non_student_and_pending_student_cannot_access_student_portal(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $pending = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'year_level' => 1, 'account_status' => 'pending']);

        $this->actingAs($instructor)->get(route('student.dashboard'))->assertForbidden();
        $this->actingAs($pending)->get(route('student.dashboard'))->assertForbidden();
    }

    /** @return array{User, AcademicSection, Subject, Room, User} */
    private function fixtures(): array
    {
        $section = AcademicSection::create(['course' => 'BSIT', 'name' => 'North', 'year_level' => 1, 'academic_year' => '2026-2027', 'semester' => 'All']);
        $student = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'year_level' => 1, 'academic_section_id' => $section->id, 'account_status' => 'active']);
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $subject = Subject::create(['course' => 'BSIT', 'code' => 'IT101', 'name' => 'Introduction to Computing', 'subject_type' => 'Lecture', 'year_level' => 1, 'semester' => '1st', 'units' => 3]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Lab 1']);

        return [$student, $section, $subject, $room, $instructor];
    }

    private function schedule(AcademicSection $section, Subject $subject, Room $room, User $instructor, string $day, string $start, string $end): ClassSchedule
    {
        return ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => $day,
            'start_time' => $start, 'end_time' => $end,
        ]);
    }
}
