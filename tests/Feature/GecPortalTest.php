<?php

namespace Tests\Feature;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GecPortalTest extends TestCase
{
    use RefreshDatabase;

    private function gecUser(): User
    {
        return User::factory()->create(['role' => 'gec', 'course' => 'GEC']);
    }

    public function test_non_gec_user_cannot_access_gec_portal(): void
    {
        $student = User::factory()->create(['role' => 'student', 'course' => 'BSIT']);

        $this->actingAs($student)->get(route('gec.dashboard'))->assertForbidden();
    }

    public function test_gec_user_can_view_dashboard(): void
    {
        $gec = $this->gecUser();

        $this->actingAs($gec)->get(route('gec.dashboard'))->assertOk();
    }

    public function test_instructor_registering_under_gec_course_lands_pending_and_can_be_approved(): void
    {
        $this->post(route('register'), [
            'role' => 'instructor',
            'course' => 'GEC',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'employment_type' => 'full_time',
        ])->assertRedirect();

        $instructor = User::where('email', 'maria.santos@example.com')->firstOrFail();
        $this->assertSame('GEC', $instructor->course);
        $this->assertSame('pending', $instructor->account_status);

        $gec = $this->gecUser();
        $this->actingAs($gec)->patch(route('gec.instructors.approve', $instructor))
            ->assertRedirect();
        $this->assertSame('active', $instructor->fresh()->account_status);

        $this->actingAs($gec)->get(route('gec.instructors.index'))
            ->assertOk()
            ->assertSee('Maria Santos');
    }

    public function test_gec_can_manage_minor_subjects_across_departments(): void
    {
        $gec = $this->gecUser();

        $this->actingAs($gec)->post(route('gec.subjects.store'), [
            'course' => 'BSBA',
            'code' => 'GE 101',
            'name' => 'Understanding the Self',
            'subject_type' => 'Lecture',
            'year_level' => 1,
            'semester' => '1st',
            'curriculum' => 'New',
            'units' => 3,
        ])->assertRedirect();

        $subject = Subject::where('code', 'GE 101')->firstOrFail();
        $this->assertSame('BSBA', $subject->course);
        $this->assertSame('Minor', $subject->classification);

        $this->actingAs($gec)->get(route('gec.subjects.index'))
            ->assertOk()
            ->assertSee('GE 101')
            ->assertSee('BSBA');

        $this->actingAs($gec)->delete(route('gec.subjects.destroy', $subject))
            ->assertRedirect();
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_gec_cannot_manage_a_major_subject_via_minor_subjects_page(): void
    {
        $gec = $this->gecUser();
        $majorSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 111', 'name' => 'Intro to Computing',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);

        $this->actingAs($gec)->delete(route('gec.subjects.destroy', $majorSubject))->assertNotFound();
    }

    public function test_gec_created_minor_subject_is_not_visible_or_manageable_on_the_departments_own_portal(): void
    {
        $gec = $this->gecUser();
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA']);

        $this->actingAs($gec)->post(route('gec.subjects.store'), [
            'course' => 'BSBA',
            'code' => 'GE 105',
            'name' => 'Life and Works of Rizal',
            'subject_type' => 'Lecture',
            'year_level' => 1,
            'semester' => '1st',
            'curriculum' => 'New',
            'units' => 3,
        ])->assertRedirect();

        $gecSubject = Subject::where('code', 'GE 105')->firstOrFail();
        $this->assertTrue($gecSubject->managed_by_gec);

        // A subject the department's own Dean already created independently
        // (e.g. before GEC existed) must remain visible on their own page.
        $deanOwnSubject = Subject::create([
            'course' => 'BSBA', 'code' => 'GE 106', 'name' => 'Dean-authored Elective',
            'subject_type' => 'Lecture', 'classification' => 'Minor',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $this->assertFalse($deanOwnSubject->fresh()->managed_by_gec);

        $response = $this->actingAs($dean)->get(route('dean.subjects.index'));
        $response->assertOk()->assertDontSee('GE 105')->assertSee('GE 106');

        $this->actingAs($dean)->get(route('dean.subjects.edit', $gecSubject))->assertNotFound();
        $this->actingAs($dean)->delete(route('dean.subjects.destroy', $gecSubject))->assertNotFound();
        $this->assertDatabaseHas('subjects', ['id' => $gecSubject->id]);

        // Still fully visible and manageable from the GEC side.
        $this->actingAs($gec)->get(route('gec.subjects.index'))->assertOk()->assertSee('GE 105');
    }

    public function test_gec_assigns_its_own_instructor_to_a_minor_subject_with_capacity_check(): void
    {
        $gec = $this->gecUser();
        $gecInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active',
            'employment_type' => 'full_time',
        ]);
        $subject = Subject::create([
            'course' => 'BSED', 'code' => 'GE 102', 'name' => 'Purposive Communication',
            'subject_type' => 'Lecture', 'classification' => 'Minor',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);

        $this->actingAs($gec)->post(route('gec.subject-assignments.store'), [
            'semester' => '1st',
            'year_level' => 1,
            'subject_id' => $subject->id,
            'instructor_ids' => [$gecInstructor->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('subject_instructor', [
            'subject_id' => $subject->id,
            'instructor_id' => $gecInstructor->id,
            'priority' => 1,
        ]);
    }

    public function test_gec_can_generate_minor_subject_schedule_for_a_department_using_gec_instructors(): void
    {
        $gec = $this->gecUser();
        $gecInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active',
            'employment_type' => 'full_time',
        ]);
        $section = AcademicSection::create([
            'course' => 'BEED', 'name' => '2 - Alpha', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BEED', 'code' => 'GE 103', 'name' => 'Mathematics in the Modern World',
            'subject_type' => 'Lecture', 'classification' => 'Minor',
            'year_level' => 2, 'semester' => '1st', 'units' => 3,
        ]);
        $subject->instructors()->attach($gecInstructor->id, ['priority' => 1]);

        $this->actingAs($gec)->post(route('gec.schedules.store'), [
            'department' => 'BEED',
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'curriculum' => 'New',
            'year_levels' => ['2'],
            'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $schedule = ClassSchedule::where('subject_id', $subject->id)->where('section_id', $section->id)->first();
        $this->assertNotNull($schedule);
        $this->assertSame('BEED', $schedule->course);
        $this->assertSame($gecInstructor->id, $schedule->instructor_id);
        $this->assertNull($schedule->room_id, 'Minor subjects should always land as TBA, never claim a real room.');
    }

    public function test_gec_timetable_and_archive_list_schedules_across_departments(): void
    {
        $gec = $this->gecUser();
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active']);
        $section = AcademicSection::create([
            'course' => 'BSHM', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSHM', 'code' => 'GE 104', 'name' => 'Art Appreciation',
            'subject_type' => 'Lecture', 'classification' => 'Minor',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $schedule = ClassSchedule::create([
            'course' => 'BSHM', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => null,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => 'M - W',
            'start_time' => '08:30', 'end_time' => '10:00',
        ]);

        $this->actingAs($gec)->get(route('gec.timetable.index'))
            ->assertOk()
            ->assertSee('BSHM')
            ->assertSee('GE 104');

        $this->actingAs($gec)->delete(route('gec.timetable.destroy', $schedule))->assertRedirect();
        $this->assertSoftDeleted('class_schedules', ['id' => $schedule->id]);

        $this->actingAs($gec)->get(route('gec.archive.index'))
            ->assertOk()
            ->assertSee('BSHM')
            ->assertSee('GE 104');

        $this->actingAs($gec)->patch(route('gec.archive.restore', $schedule->id))->assertRedirect();
        $this->assertDatabaseHas('class_schedules', ['id' => $schedule->id, 'deleted_at' => null]);
    }

    public function test_gec_is_seeded_as_its_own_department_and_scopes_instructor_pool(): void
    {
        $this->assertDatabaseHas('departments', ['code' => 'GEC']);

        $gecDepartment = Department::where('code', 'GEC')->firstOrFail();
        $gecInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active']);
        $bsitInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);

        $this->assertSame($gecDepartment->id, $gecInstructor->department_id);
        $this->assertNotSame($gecDepartment->id, $bsitInstructor->department_id);
    }

    public function test_gec_can_delete_all_instructor_accounts_in_its_department(): void
    {
        $gec = $this->gecUser();
        $gecInstructorOne = User::factory()->create(['role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active']);
        $gecInstructorTwo = User::factory()->create(['role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active']);
        $bsitInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $subject = Subject::create([
            'course' => 'BEED', 'code' => 'GE 107', 'name' => 'Test Subject',
            'subject_type' => 'Lecture', 'classification' => 'Minor',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $subject->instructors()->attach($gecInstructorOne->id, ['priority' => 1]);

        $this->actingAs($gec)->delete(route('gec.instructors.destroy-all'))->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $gecInstructorOne->id]);
        $this->assertSoftDeleted('users', ['id' => $gecInstructorTwo->id]);
        $this->assertDatabaseMissing('subject_instructor', ['subject_id' => $subject->id, 'instructor_id' => $gecInstructorOne->id]);
        // Instructors outside the GEC department must be untouched.
        $this->assertDatabaseHas('users', ['id' => $bsitInstructor->id, 'deleted_at' => null]);
    }

    public function test_deleting_all_instructor_accounts_with_none_present_shows_an_error(): void
    {
        $gec = $this->gecUser();

        $this->actingAs($gec)->delete(route('gec.instructors.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_gec_can_toggle_semester_availability(): void
    {
        $gec = $this->gecUser();

        $this->actingAs($gec)->patch(route('gec.settings.semesters'), [
            'semester_first_enabled' => '1',
            'semester_second_enabled' => '0',
            'semester_summer_enabled' => '0',
        ])->assertRedirect();

        $gecDepartment = Department::where('code', 'GEC')->firstOrFail();
        $this->assertTrue($gecDepartment->semester_first_enabled);
        $this->assertFalse($gecDepartment->semester_second_enabled);
        $this->assertFalse($gecDepartment->semester_summer_enabled);
        $this->assertSame(['1st'], $gecDepartment->enabledSemesterCodes());
    }
}
