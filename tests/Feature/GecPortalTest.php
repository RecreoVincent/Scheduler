<?php

namespace Tests\Feature;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_gec_dashboard_analytics_only_use_the_active_semester(): void
    {
        $gec = $this->gecUser();
        Department::where('code', 'GEC')->firstOrFail()->update([
            'semester_first_enabled' => true,
            'semester_second_enabled' => false,
            'semester_summer_enabled' => false,
        ]);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active',
        ]);
        $firstSection = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - First', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $secondSection = AcademicSection::create([
            'course' => 'BSBA', 'name' => '1 - Second', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $firstSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'GE 101', 'name' => 'First Semester Minor',
            'subject_type' => 'Lecture', 'classification' => 'Minor', 'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $secondSubject = Subject::create([
            'course' => 'BSBA', 'code' => 'GE 201', 'name' => 'Second Semester Minor',
            'subject_type' => 'Lecture', 'classification' => 'Minor', 'year_level' => 1, 'semester' => '2nd', 'units' => 3,
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'GEC Room', 'room_type' => 'Lecture']);
        ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $firstSection->id, 'subject_id' => $firstSubject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => 'M - W', 'start_time' => '08:30', 'end_time' => '10:00',
        ]);
        ClassSchedule::create([
            'course' => 'BSBA', 'section_id' => $secondSection->id, 'subject_id' => $secondSubject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '2nd', 'day' => 'M - W', 'start_time' => '10:00', 'end_time' => '11:30',
        ]);

        $this->actingAs($gec)->get(route('gec.dashboard'))
            ->assertOk()
            ->assertSee('1st Semester General Education analytics')
            ->assertSee('GE 101')
            ->assertDontSee('GE 201')
            ->assertViewHas('statistics', fn (array $statistics): bool => $statistics === [
                'instructors' => 1,
                'subjects' => 1,
                'assignments' => 0,
                'schedules' => 1,
            ]);
    }

    public function test_gec_cannot_delete_a_minor_subject_from_an_inactive_semester(): void
    {
        $gec = $this->gecUser();
        Department::where('code', 'GEC')->firstOrFail()->update([
            'semester_first_enabled' => true,
            'semester_second_enabled' => false,
            'semester_summer_enabled' => false,
        ]);
        $firstSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'GE 101', 'name' => 'First Semester Minor',
            'subject_type' => 'Lecture', 'classification' => 'Minor', 'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $secondSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'GE 201', 'name' => 'Second Semester Minor',
            'subject_type' => 'Lecture', 'classification' => 'Minor', 'year_level' => 1, 'semester' => '2nd', 'units' => 3,
        ]);

        $this->actingAs($gec)->delete(route('gec.subjects.destroy', $secondSubject))->assertNotFound();
        $this->assertModelExists($secondSubject);

        $this->actingAs($gec)->delete(route('gec.subjects.destroy', $firstSubject))->assertRedirect();
        $this->assertModelMissing($firstSubject);
        $this->assertModelExists($secondSubject);
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

    public function test_gec_allows_and_normalizes_minor_subject_names_up_to_the_database_limit(): void
    {
        $gec = $this->gecUser();
        $subjectName = '  '.str_repeat('A', 253).'  ';

        $this->actingAs($gec)->post(route('gec.subjects.store'), [
            'course' => 'BEED',
            'code' => 'PATHFit 3',
            'name' => $subjectName,
            'subject_type' => 'Lecture',
            'year_level' => 2,
            'curriculum' => 'New',
            'units' => 3,
        ])->assertRedirect(route('gec.subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'course' => 'BEED',
            'code' => 'PATHFit 3',
            'name' => str_repeat('A', 253),
        ]);
    }

    public function test_gec_can_import_minor_subjects_and_download_the_csv_template(): void
    {
        $gec = $this->gecUser();
        $file = UploadedFile::fake()->createWithContent('subjects.csv', "code,name,subject_type,classification,year_level,semester,curriculum,units\nGE101,Understanding the Self,Lecture,Major,1,1st,New,3\n");

        $this->actingAs($gec)
            ->post(route('gec.subjects.import'), [
                'import_course' => 'BSIT',
                'csv_file' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', [
            'course' => 'BSIT',
            'code' => 'GE101',
            'classification' => 'Minor',
            'managed_by_gec' => true,
            'semester' => '1st',
        ]);

        $this->actingAs($gec)
            ->get(route('gec.subjects.import-template'))
            ->assertOk()
            ->assertDownload('gec-subject-import-template.csv');

        $this->actingAs($gec)
            ->get(route('gec.subjects.index'))
            ->assertOk()
            ->assertSee('Import Subjects')
            ->assertSee('Download CSV Template');
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

    public function test_gec_can_assign_up_to_ten_instructor_priorities_to_a_minor_subject(): void
    {
        $gec = $this->gecUser();
        $instructors = User::factory()->count(10)->create([
            'role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active',
            'employment_type' => 'full_time',
        ]);
        $subject = Subject::create([
            'course' => 'BSED', 'code' => 'GE 110', 'name' => 'Art Appreciation',
            'subject_type' => 'Lecture', 'classification' => 'Minor',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);

        $this->actingAs($gec)->post(route('gec.subject-assignments.store'), [
            'semester' => '1st',
            'year_level' => 1,
            'subject_id' => $subject->id,
            'instructor_ids' => $instructors->modelKeys(),
        ])->assertRedirect();

        $this->assertSame(10, $subject->fresh('instructors')->instructors->count());
        $this->assertDatabaseHas('subject_instructor', [
            'subject_id' => $subject->id,
            'instructor_id' => $instructors->last()->id,
            'priority' => 10,
        ]);
    }

    public function test_gec_instructor_units_warns_when_minor_subject_units_exceed_active_gec_instructor_capacity(): void
    {
        $gec = $this->gecUser();
        Department::where('code', 'GEC')->firstOrFail()->update([
            'semester_first_enabled' => true,
            'semester_second_enabled' => false,
            'semester_summer_enabled' => false,
            'default_unit_limit_full_time' => 30,
        ]);
        User::factory()->create([
            'role' => 'instructor',
            'course' => 'GEC',
            'account_status' => 'active',
            'employment_type' => 'full_time',
            'teaching_unit_limit' => 30,
        ]);

        foreach ([
            ['BSIT', 'GE 201'],
            ['BSBA', 'GE 202'],
            ['BSHM', 'GE 203'],
            ['BEED', 'GE 204'],
        ] as [$course, $code]) {
            Subject::create([
                'course' => $course,
                'code' => $code,
                'name' => "Subject {$code}",
                'subject_type' => 'Lecture',
                'classification' => 'Minor',
                'year_level' => 1,
                'semester' => '1st',
                'curriculum' => 'New',
                'units' => 10,
            ]);
        }

        $this->actingAs($gec)->get(route('gec.instructor-units.index'))
            ->assertOk()
            ->assertSee('GEC instructor capacity shortage')
            ->assertSee('40 units')
            ->assertSee('30 units')
            ->assertSee('shortfall is')
            ->assertSee('10 units')
            ->assertSee('1 additional full-time GEC instructor');
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
        $room = Room::create(['course' => 'BEED', 'name' => 'Education 101', 'room_type' => 'Lecture']);
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
        $this->assertSame($room->id, $schedule->room_id);
        $this->assertSame(90, (int) ((strtotime($schedule->end_time) - strtotime($schedule->start_time)) / 60));
    }

    public function test_gec_cannot_generate_a_minor_subject_schedule_without_an_assigned_instructor(): void
    {
        $gec = $this->gecUser();
        User::factory()->create([
            'role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active',
            'employment_type' => 'full_time',
        ]);
        AcademicSection::create([
            'course' => 'BSIT', 'name' => '2 - Alpha', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'GE 104', 'name' => 'Art Appreciation',
            'subject_type' => 'Lecture', 'classification' => 'Minor',
            'year_level' => 2, 'semester' => '1st', 'units' => 3,
        ]);

        $this->actingAs($gec)->post(route('gec.schedules.store'), [
            'department' => 'BSIT',
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'curriculum' => 'New',
            'year_levels' => ['2'],
            'number_of_sections' => 1,
        ])->assertRedirect()
            ->assertSessionHas('error', "The schedule cannot be created because these Minor subjects have no active assigned instructor: BSIT: {$subject->code}.")
            ->assertSessionHas('error_note', fn (string $note): bool => str_contains($note, 'Subject Assignment'));

        $this->assertDatabaseMissing('class_schedules', ['subject_id' => $subject->id]);
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

    public function test_deleted_gec_instructor_account_appears_on_archive_page_and_can_be_restored(): void
    {
        $gec = $this->gecUser();
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active']);

        $this->actingAs($gec)->delete(route('gec.instructors.destroy', $instructor))->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $instructor->id]);

        $this->actingAs($gec)->get(route('gec.archive.index'))
            ->assertOk()
            ->assertSee('Deleted Instructor Accounts')
            ->assertSee($instructor->email);

        $this->actingAs($gec)
            ->patch(route('gec.archive.accounts.restore', $instructor->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $instructor->id, 'deleted_at' => null]);
    }

    public function test_deleted_gec_instructor_account_can_be_permanently_deleted_from_archive_page(): void
    {
        $gec = $this->gecUser();
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active']);
        $this->actingAs($gec)->delete(route('gec.instructors.destroy', $instructor))->assertRedirect();

        $this->actingAs($gec)
            ->delete(route('gec.archive.accounts.destroy', $instructor->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $instructor->id]);
    }

    public function test_gec_can_permanently_delete_all_deleted_instructor_accounts_at_once(): void
    {
        $gec = $this->gecUser();
        $instructorOne = User::factory()->create(['role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active']);
        $instructorTwo = User::factory()->create(['role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active']);
        $bsitInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $this->actingAs($gec)->delete(route('gec.instructors.destroy', $instructorOne))->assertRedirect();
        $this->actingAs($gec)->delete(route('gec.instructors.destroy', $instructorTwo))->assertRedirect();
        $bsitInstructor->delete();

        $this->actingAs($gec)->get(route('gec.archive.index'))
            ->assertOk()
            ->assertSee('Delete All Permanently');

        $this->actingAs($gec)
            ->delete(route('gec.archive.accounts.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $instructorOne->id]);
        $this->assertDatabaseMissing('users', ['id' => $instructorTwo->id]);
        $this->assertSoftDeleted('users', ['id' => $bsitInstructor->id]);
    }

    public function test_gec_cannot_restore_or_delete_a_non_gec_deleted_instructor(): void
    {
        $gec = $this->gecUser();
        $bsitInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $bsitInstructor->delete();

        $this->actingAs($gec)->patch(route('gec.archive.accounts.restore', $bsitInstructor->id))->assertNotFound();
        $this->actingAs($gec)->delete(route('gec.archive.accounts.destroy', $bsitInstructor->id))->assertNotFound();
        $this->assertSoftDeleted('users', ['id' => $bsitInstructor->id]);
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

    public function test_instructor_import_accepts_loosely_formatted_employment_type_values(): void
    {
        $gec = $this->gecUser();
        $csv = "first_name,last_name,email,employment_type,outside_work_end_time\n"
            ."Ana,Reyes,ana.reyes@example.test,Full-Time,\n"
            ."Ben,Santos,ben.santos@example.test,Part Time,\n"
            ."Cid,Lopez,cid.lopez@example.test,Industry Part-Time,17:00\n"
            ."Dex,Cruz,dex.cruz@example.test,flexible parttime,\n";
        $file = UploadedFile::fake()->createWithContent('instructors.csv', $csv);

        $this->actingAs($gec)->post(route('gec.instructors.import'), ['csv_file' => $file])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'ana.reyes@example.test', 'employment_type' => 'full_time']);
        $this->assertDatabaseHas('users', ['email' => 'ben.santos@example.test', 'employment_type' => 'flexible_part_time']);
        $this->assertDatabaseHas('users', ['email' => 'cid.lopez@example.test', 'employment_type' => 'industry_part_time']);
        $this->assertDatabaseHas('users', ['email' => 'dex.cruz@example.test', 'employment_type' => 'flexible_part_time']);
    }

    public function test_gec_can_toggle_semester_availability(): void
    {
        $gec = $this->gecUser();

        $this->actingAs($gec)->patch(route('gec.settings.semesters'), [
            'active_semester' => 'first',
        ])->assertRedirect();

        $gecDepartment = Department::where('code', 'GEC')->firstOrFail();
        $this->assertTrue($gecDepartment->semester_first_enabled);
        $this->assertFalse($gecDepartment->semester_second_enabled);
        $this->assertFalse($gecDepartment->semester_summer_enabled);
        $this->assertSame(['1st'], $gecDepartment->enabledSemesterCodes());
    }

    public function test_gec_cannot_save_multiple_active_semesters(): void
    {
        $gec = $this->gecUser();
        $department = Department::where('code', 'GEC')->firstOrFail();
        $department->update([
            'semester_first_enabled' => false,
            'semester_second_enabled' => true,
            'semester_summer_enabled' => false,
        ]);

        $this->actingAs($gec)->patch(route('gec.settings.semesters'), [
            'semester_first_enabled' => '1',
            'semester_second_enabled' => '1',
            'semester_summer_enabled' => '0',
        ])->assertRedirect()->assertSessionHasErrors('semester_availability');

        $department->refresh();
        $this->assertFalse($department->semester_first_enabled);
        $this->assertTrue($department->semester_second_enabled);
        $this->assertFalse($department->semester_summer_enabled);
    }

    public function test_gec_instructor_edit_modal_omits_password_fields(): void
    {
        $gec = $this->gecUser();
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active',
        ]);

        $this->actingAs($gec)
            ->get(route('gec.instructors.index', ['edit' => $instructor->id]))
            ->assertOk()
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="password_confirmation"', false);
    }
}
