<?php

namespace Tests\Feature;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\CrossDepartmentInstructorRequest;
use App\Models\Department;
use App\Models\Ms365StudentAccount;
use App\Models\Room;
use App\Models\Subject;
use App\Models\SubjectEndorsement;
use App\Models\User;
use App\Services\ClassScheduleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DeanPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_dean_can_submit_a_subject_endorsement_to_another_department(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $destinationDean = User::factory()->create([
            'role' => 'dean',
            'course' => 'BSBA',
            'account_status' => 'active',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT',
            'code' => 'ITE 201',
            'name' => 'Object-Oriented Programming',
            'subject_type' => 'Lecture',
            'classification' => 'Major',
            'year_level' => 2,
            'semester' => '1st',
            'curriculum' => 'New',
            'units' => 3,
        ]);
        Notification::fake();

        $this->actingAs($dean)->post(route('dean.subject-endorsements.store'), [
            'to_department' => 'BSBA',
            'year_level' => 2,
            'subject_id' => $subject->id,
        ])->assertRedirect(route('dean.subject-endorsements.index'));

        $this->assertDatabaseHas('subject_endorsements', [
            'from_department' => 'BSIT',
            'to_department' => 'BSBA',
            'subject_code' => 'ITE 201',
            'subject_name' => 'Object-Oriented Programming',
            'subject_type' => 'Lecture',
            'units' => 3,
            'subject_id' => $subject->id,
            'endorsed_by' => $dean->id,
        ]);

        $endorsement = SubjectEndorsement::firstOrFail();
        Notification::assertSentTo(
            $destinationDean,
            \App\Notifications\SubjectEndorsementReceivedNotification::class,
            fn ($notification): bool => $notification->toArray($destinationDean)['url'] === route('dean.subject-endorsements.schedule.create', $endorsement),
        );
        $this->actingAs($dean)->get(route('dean.subject-endorsements.index'))
            ->assertOk()
            ->assertSee('Subject Endorsement')
            ->assertSee('name="year_level"', false)
            ->assertSee('name="subject_id"', false)
            ->assertSee('ITE 201')
            ->assertSee($endorsement->to_department);

        $this->actingAs($destinationDean)
            ->get(route('dean.subject-endorsements.schedule.create', $endorsement))
            ->assertOk()
            ->assertSee('Schedule Endorsed Subject')
            ->assertSee('ITE 201');
    }

    public function test_dean_subject_endorsements_only_list_and_accept_subjects_from_the_active_semester(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        Department::where('code', 'BSIT')->firstOrFail()->update([
            'semester_first_enabled' => false,
            'semester_second_enabled' => true,
            'semester_summer_enabled' => false,
        ]);
        $firstSemesterSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 101', 'name' => 'First Semester Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $secondSemesterSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 201', 'name' => 'Second Semester Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '2nd', 'units' => 3,
        ]);

        $this->actingAs($dean)->get(route('dean.subject-endorsements.index'))
            ->assertOk()
            ->assertSee($secondSemesterSubject->code)
            ->assertDontSee($firstSemesterSubject->code);

        $this->actingAs($dean)->post(route('dean.subject-endorsements.store'), [
            'to_department' => 'BSBA',
            'year_level' => 1,
            'subject_id' => $firstSemesterSubject->id,
        ])->assertSessionHasErrors('subject_id');
    }

    public function test_receiving_dean_can_create_a_schedule_for_an_endorsed_subject(): void
    {
        $destinationDean = User::factory()->create([
            'role' => 'dean',
            'course' => 'BSBA',
            'account_status' => 'active',
        ]);
        $destinationInstructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSBA',
            'account_status' => 'active',
            'employment_type' => 'full_time',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT',
            'code' => 'ITE 202',
            'name' => 'Data Structures',
            'subject_type' => 'Lecture',
            'classification' => 'Major',
            'year_level' => 2,
            'semester' => '1st',
            'curriculum' => 'New',
            'units' => 3,
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT',
            'name' => '2 - Alpha',
            'year_level' => 2,
            'academic_year' => '2026-2027',
            'semester' => 'All',
        ]);
        Room::create([
            'course' => 'BSBA',
            'name' => 'Business Lecture 1',
            'room_type' => 'Lecture',
            'capacity' => 40,
        ]);
        $endorsement = SubjectEndorsement::create([
            'subject_id' => $subject->id,
            'from_department' => 'BSIT',
            'to_department' => 'BSBA',
            'subject_code' => $subject->code,
            'subject_name' => $subject->name,
            'subject_type' => $subject->subject_type,
            'units' => $subject->units,
        ]);

        $this->actingAs($destinationDean)->post(route('dean.subject-endorsements.schedule.store', $endorsement), [
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'number_of_sections' => 1,
            'instructor_ids' => [$destinationInstructor->id, null, null, null],
        ])->assertRedirect(route('dean.subject-endorsements.schedule.create', $endorsement));

        $this->assertDatabaseHas('class_schedules', [
            'course' => 'BSIT',
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'instructor_id' => $destinationInstructor->id,
            'academic_year' => '2026-2027',
            'semester' => '1st',
        ]);
        $this->assertSame($destinationDean->id, $endorsement->fresh()->scheduled_by);
        $this->assertNotNull($endorsement->fresh()->scheduled_at);
    }

    public function test_dean_can_delete_completed_endorsement_history_without_deleting_schedules(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $sentEndorsement = SubjectEndorsement::create([
            'from_department' => 'BSIT',
            'to_department' => 'BSBA',
            'subject_code' => 'ITE 301',
            'subject_name' => 'Systems Analysis',
            'subject_type' => 'Lecture',
            'units' => 3,
            'scheduled_at' => now(),
        ]);
        $receivedEndorsement = SubjectEndorsement::create([
            'from_department' => 'BSBA',
            'to_department' => 'BSIT',
            'subject_code' => 'BA 301',
            'subject_name' => 'Business Systems',
            'subject_type' => 'Lecture',
            'units' => 3,
            'scheduled_at' => now(),
        ]);

        $this->actingAs($dean)
            ->get(route('dean.subject-endorsements.index'))
            ->assertOk()
            ->assertSee('Delete All History');

        $this->actingAs($dean)
            ->delete(route('dean.subject-endorsements.history.destroy'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($sentEndorsement->fresh()->from_department_archived_at);
        $this->assertNull($sentEndorsement->fresh()->to_department_archived_at);
        $this->assertNotNull($receivedEndorsement->fresh()->to_department_archived_at);
        $this->assertNull($receivedEndorsement->fresh()->from_department_archived_at);
        $this->assertModelExists($sentEndorsement);
        $this->assertModelExists($receivedEndorsement);
    }

    public function test_dean_student_list_displays_the_unique_active_ms365_email(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->create([
            'role' => 'student', 'course' => 'BSIT', 'account_status' => 'active',
            'first_name' => 'Maria', 'last_name' => 'Santos',
            'email' => 'student-record@roster.mcc.local',
        ]);
        Ms365StudentAccount::create([
            'email' => 'maria.santos@mcc.edu.ph',
            'display_name' => 'Maria Santos',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
        ]);

        $this->actingAs($dean)
            ->get(route('dean.students.index'))
            ->assertOk()
            ->assertSee('MS365 Email')
            ->assertSee('maria.santos@mcc.edu.ph')
            ->assertDontSee('student-record@roster.mcc.local');
    }

    public function test_dean_student_list_prefers_the_ms365_student_number_match(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->create([
            'role' => 'student', 'course' => 'BSIT', 'account_status' => 'active',
            'student_id' => '2026-0019', 'first_name' => 'Roster', 'last_name' => 'Student',
        ]);
        Ms365StudentAccount::create([
            'email' => 'student.official@mcc.edu.ph',
            'student_number' => '2026-0019',
            'display_name' => 'Different MS365 Name',
            'first_name' => 'Different',
            'last_name' => 'Name',
        ]);

        $this->actingAs($dean)
            ->get(route('dean.students.index'))
            ->assertOk()
            ->assertSee('student.official@mcc.edu.ph');
    }

    public function test_dean_student_list_matches_an_ms365_first_name_that_includes_the_students_middle_name(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->create([
            'role' => 'student', 'course' => 'BSIT', 'account_status' => 'active',
            'first_name' => 'Ralf', 'middle_name' => 'Francisco', 'last_name' => 'Cueva',
        ]);
        Ms365StudentAccount::create([
            'email' => 'ralf.cueva@mcc.edu.ph',
            'display_name' => 'Ralf Francisco Cueva',
            'first_name' => 'Ralf Francisco',
            'last_name' => 'Cueva',
        ]);

        $this->actingAs($dean)
            ->get(route('dean.students.index'))
            ->assertOk()
            ->assertSee('ralf.cueva@mcc.edu.ph');
    }

    public function test_dean_can_delete_all_student_accounts_in_its_department(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $studentOne = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'account_status' => 'active']);
        $studentTwo = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'account_status' => 'active']);
        $bsbaStudent = User::factory()->create(['role' => 'student', 'course' => 'BSBA', 'account_status' => 'active']);

        $this->actingAs($dean)->get(route('dean.students.index'))
            ->assertOk()
            ->assertSee('Delete All Accounts');

        $this->actingAs($dean)->delete(route('dean.students.destroy-all'))->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $studentOne->id]);
        $this->assertSoftDeleted('users', ['id' => $studentTwo->id]);
        // Students outside the BSIT department must be untouched.
        $this->assertDatabaseHas('users', ['id' => $bsbaStudent->id, 'deleted_at' => null]);
    }

    public function test_deleting_all_dean_student_accounts_with_none_present_shows_an_error(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);

        $this->actingAs($dean)->delete(route('dean.students.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_dean_can_delete_all_instructor_accounts_in_its_department(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructorOne = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $instructorTwo = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $bsbaInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active']);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'IT301', 'name' => 'Test Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $subject->instructors()->attach($instructorOne->id, ['priority' => 1]);

        $this->actingAs($dean)->get(route('dean.instructors.index'))
            ->assertOk()
            ->assertSee('Delete All Accounts');

        $this->actingAs($dean)->delete(route('dean.instructors.destroy-all'))->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $instructorOne->id]);
        $this->assertSoftDeleted('users', ['id' => $instructorTwo->id]);
        $this->assertDatabaseMissing('subject_instructor', ['subject_id' => $subject->id, 'instructor_id' => $instructorOne->id]);
        // Instructors outside the BSIT department must be untouched.
        $this->assertDatabaseHas('users', ['id' => $bsbaInstructor->id, 'deleted_at' => null]);
    }

    public function test_deleting_all_dean_instructor_accounts_with_none_present_shows_an_error(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);

        $this->actingAs($dean)->delete(route('dean.instructors.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_all_printable_reports_include_an_exit_button(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);

        foreach (['teaching-loads', 'instructor-workload', 'class-schedules'] as $type) {
            $this->actingAs($dean)
                ->get(route('dean.print.report', $type))
                ->assertOk()
                ->assertSee('class="exit-button"', false)
                ->assertSee('>Exit</button>', false);
        }
    }

    public function test_dean_printable_reports_only_include_the_active_semester(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        Department::where('code', 'BSIT')->firstOrFail()->update([
            'semester_first_enabled' => false,
            'semester_second_enabled' => true,
            'semester_summer_enabled' => false,
        ]);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - Print', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $firstSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 101', 'name' => 'First Semester Print Only',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $secondSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 201', 'name' => 'Second Semester Print Only',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '2nd', 'units' => 3,
        ]);

        foreach ([
            [$firstSubject, '1st', '08:30'],
            [$secondSubject, '2nd', '11:00'],
        ] as [$subject, $semester, $startTime]) {
            ClassSchedule::create([
                'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
                'instructor_id' => $instructor->id, 'academic_year' => '2026-2027',
                'semester' => $semester, 'day' => 'M - W', 'start_time' => $startTime, 'end_time' => '12:30',
            ]);
        }

        foreach (['teaching-loads', 'instructor-workload', 'class-schedules'] as $type) {
            $this->actingAs($dean)
                ->get(route('dean.print.report', $type))
                ->assertOk()
                ->assertSee('Second Semester Print Only')
                ->assertDontSee('First Semester Print Only');
        }
    }

    public function test_class_schedule_report_prints_each_section_in_the_official_format(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
            'first_name' => 'Emily', 'middle_name' => null, 'last_name' => 'Ilustrisimo',
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - North', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 111', 'name' => 'Introduction in Computing',
            'subject_type' => 'Laboratory', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);
        ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => 'M - W',
            'start_time' => '07:00', 'end_time' => '08:30',
        ]);

        $this->actingAs($dean)->get(route('dean.print.report', 'class-schedules'))
            ->assertOk()
            ->assertSeeInOrder([
                'Madridejos Community College',
                'Information Technology Department',
                'First Semester, A.Y. 2026-2027',
                'Bachelor of Science in Information Technology',
                'BSIT-1NORTH',
                'Time',
                'Days',
                'Subject Code',
                'Subject Description',
                'Unit',
                'Room',
                'Instructor',
                'ITE 111',
                'Introduction in Computing',
                'Total No. of Units',
            ])
            ->assertDontSee('Tentative Class Schedule')
            ->assertSee('Lab 1')
            ->assertSee('Emily Ilustrisimo')
            ->assertSee('images/mcc-college-logo.png', false)
            ->assertSee('images/bsit-department-logo.jpg', false);
    }

    public function test_instructor_workload_report_prints_an_individual_faculty_load_sheet(): void
    {
        $dean = User::factory()->create([
            'role' => 'dean', 'course' => 'BSIT', 'first_name' => 'Dino',
            'middle_name' => 'Lopez', 'last_name' => 'Ilustrisimo',
        ]);
        Department::where('code', 'BSIT')->firstOrFail()->update([
            'semester_first_enabled' => false,
            'semester_second_enabled' => true,
            'semester_summer_enabled' => false,
        ]);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
            'employment_type' => 'full_time', 'first_name' => 'Danilo',
            'middle_name' => 'Bautista', 'last_name' => 'Villarino',
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '2 - East', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 221', 'name' => 'Data Structures and Algorithms',
            'subject_type' => 'Laboratory', 'classification' => 'Major',
            'year_level' => 2, 'semester' => '2nd', 'units' => 3,
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'IT-LR2', 'room_type' => 'Laboratory']);
        ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '2nd', 'day' => 'M - W',
            'start_time' => '08:30', 'end_time' => '10:00',
        ]);
        $borrowedInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active',
            'employment_type' => 'flexible_part_time', 'first_name' => 'Maria',
            'middle_name' => null, 'last_name' => 'Santos',
        ]);
        $borrowedSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'GE 201', 'name' => 'General Education Course',
            'subject_type' => 'Lecture', 'classification' => 'Minor',
            'year_level' => 2, 'semester' => '2nd', 'units' => 3,
        ]);
        ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $borrowedSubject->id,
            'instructor_id' => $borrowedInstructor->id, 'room_id' => null,
            'academic_year' => '2026-2027', 'semester' => '2nd', 'day' => 'F - S',
            'start_time' => '10:00', 'end_time' => '11:30',
        ]);
        $unrelatedInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSHM', 'account_status' => 'active',
            'first_name' => 'Unrelated', 'middle_name' => null, 'last_name' => 'Instructor',
        ]);
        $departmentInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
            'first_name' => 'Elmer', 'middle_name' => null, 'last_name' => 'Lasala',
        ]);

        $this->actingAs($dean)->get(route('dean.print.report', 'instructor-workload'))
            ->assertOk()
            ->assertSeeInOrder([
                'Madridejos Community College',
                'Information Technology Department',
                'Second Semester, School Year 2026-2027',
                'Individual Faculty Load Sheet',
                'Family Name:',
                'Villarino',
                'First Name:',
                'Danilo',
                'Middle Initial:',
                'B.',
                'Employment Status:',
                'A. Basic Load / Built-In',
                'ITE 221',
                'Data Structures and Algorithms',
                'BSIT-2EAST',
                'B. Other Academic-Related Functions',
                'C. Consultation Hours',
                'D. Overload',
                'Grand Total Number of Units / Hours',
                'Prepared by:',
                'Dino Lopez Ilustrisimo',
                'Conforme:',
                'Danilo Bautista Villarino',
            ])
            ->assertSee('Print Individual Faculty Load Sheets')
            ->assertSee('Export to Excel')
            ->assertSee('method="GET" action="'.route('dean.print.instructor-workload.excel').'"', false)
            ->assertSee($departmentInstructor->name)
            ->assertDontSee($borrowedInstructor->name)
            ->assertDontSee('GE 201')
            ->assertDontSee($unrelatedInstructor->name)
            ->assertSee('images/mcc-college-logo.png', false)
            ->assertSee('images/bsit-department-logo.jpg', false);
    }

    public function test_dean_can_export_individual_faculty_load_sheets_as_an_excel_workbook(): void
    {
        $dean = User::factory()->create([
            'role' => 'dean', 'course' => 'BSIT', 'first_name' => 'Dino',
            'middle_name' => 'Lopez', 'last_name' => 'Ilustrisimo',
        ]);
        Department::where('code', 'BSIT')->firstOrFail()->update([
            'semester_first_enabled' => false,
            'semester_second_enabled' => true,
            'semester_summer_enabled' => false,
        ]);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
            'employment_type' => 'full_time', 'first_name' => 'Danilo',
            'middle_name' => 'Bautista', 'last_name' => 'Villarino',
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '2 - East', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 221', 'name' => 'Data Structures and Algorithms',
            'subject_type' => 'Laboratory', 'classification' => 'Major',
            'year_level' => 2, 'semester' => '2nd', 'units' => 3,
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'IT-LR2', 'room_type' => 'Laboratory']);
        ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '2nd', 'day' => 'M - W',
            'start_time' => '07:00', 'end_time' => '09:30',
        ]);
        $firstSemesterSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 111', 'name' => 'First Semester Export Excluded',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $firstSemesterSubject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => 'F - S',
            'start_time' => '07:00', 'end_time' => '08:30',
        ]);

        $response = $this->actingAs($dean)->get(route('dean.print.instructor-workload.excel'));

        $response->assertOk()->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        )->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('PK', $response->getContent());

        $temporaryFile = tempnam(sys_get_temp_dir(), 'faculty-load-test-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());
        $zip = new \ZipArchive;

        try {
            $this->assertTrue($zip->open($temporaryFile) === true);
            $this->assertNotFalse($zip->getFromName('[Content_Types].xml'));
            $this->assertNotFalse($zip->getFromName('xl/workbook.xml'));
            $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertNotFalse($worksheet);
            $this->assertStringContainsString('DANILO', $worksheet);
            $this->assertStringContainsString('VILLARINO', $worksheet);
            $this->assertStringContainsString('ITE 221', $worksheet);
            $this->assertStringContainsString('DATA STRUCTURES AND ALGORITHMS', $worksheet);
            $this->assertStringNotContainsString('FIRST SEMESTER EXPORT EXCLUDED', $worksheet);
            $this->assertStringContainsString('<c r="G13" s="13"><v>2</v></c>', $worksheet);
            $this->assertStringContainsString('<c r="H13" s="13"><v>1</v></c>', $worksheet);
            $this->assertStringContainsString('<c r="I13" s="13"><v>5</v></c>', $worksheet);
            $this->assertStringContainsString('<c r="J13" s="13"><v>5</v></c>', $worksheet);
            $this->assertStringContainsString('BSIT-2EAST', $worksheet);
            $this->assertStringContainsString('IT-LR2', $worksheet);
            $this->assertStringContainsString('Suffix:', $worksheet);
            $this->assertStringContainsString('Regular / Full-Time', $worksheet);
            $this->assertStringContainsString('B. OTHER ACADEMIC-RELATED FUNCTIONS', $worksheet);
            $this->assertStringContainsString('No. of Students', $worksheet);
            $this->assertStringContainsString('C. CONSULTATION HOURS', $worksheet);
            $this->assertStringContainsString('Number of Hours', $worksheet);
            $this->assertStringContainsString('D. OVERLOAD', $worksheet);
            $this->assertStringContainsString('Grand Total Number of Units / Hours', $worksheet);
            $this->assertStringContainsString('Recommending Approval:', $worksheet);
            $this->assertStringContainsString('Approved by:', $worksheet);
            $this->assertStringContainsString('DR. FLORPISA A. MONTECILLO, LPT', $worksheet);
            $this->assertStringContainsString('HON. ROMEO A. VILLACERAN', $worksheet);
            $this->assertStringNotContainsString('<pane ', $worksheet);
            $this->assertNotFalse($zip->getFromName('xl/media/mcc-logo.png'));
            $this->assertNotFalse($zip->getFromName('xl/media/department-logo.jpg'));
            $this->assertNotFalse($zip->getFromName('xl/media/header-divider.png'));
            $drawing = $zip->getFromName('xl/drawings/drawing1.xml');
            $this->assertNotFalse($drawing);
            $this->assertStringContainsString('<xdr:col>0</xdr:col><xdr:colOff>285750</xdr:colOff>', $drawing);
            $this->assertStringContainsString('<xdr:rowOff>95250</xdr:rowOff>', $drawing);
            $this->assertStringContainsString('<xdr:ext cx="819150" cy="819150"/>', $drawing);
            $this->assertStringContainsString('<xdr:col>8</xdr:col><xdr:colOff>0</xdr:colOff>', $drawing);
            $this->assertStringContainsString('<xdr:rowOff>95250</xdr:rowOff>', $drawing);
            $this->assertStringContainsString('<xdr:ext cx="819150" cy="819150"/>', $drawing);
            $this->assertStringContainsString('<xdr:cNvPr id="3" name="Header Divider"/>', $drawing);
            $this->assertStringContainsString('<xdr:col>1</xdr:col><xdr:colOff>552450</xdr:colOff>', $drawing);
            $this->assertStringContainsString('<xdr:ext cx="4267200" cy="9525"/>', $drawing);
            $this->assertNotFalse($zip->getFromName('xl/drawings/_rels/drawing1.xml.rels'));
            $this->assertNotFalse($zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels'));
            $this->assertLessThan(strpos($worksheet, '<pageMargins'), strpos($worksheet, '<printOptions'));
            $this->assertLessThan(strpos($worksheet, '<pageSetup'), strpos($worksheet, '<pageMargins'));
            $this->assertLessThan(strpos($worksheet, '<drawing'), strpos($worksheet, '<pageSetup'));
        } finally {
            $zip->close();
            @unlink($temporaryFile);
        }
    }

    public function test_teaching_load_report_prints_department_summary_by_employment_type(): void
    {
        $dean = User::factory()->create([
            'role' => 'dean', 'course' => 'BSIT', 'first_name' => 'Dino',
            'middle_name' => 'Lopez', 'last_name' => 'Ilustrisimo',
        ]);
        $fullTime = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
            'employment_type' => 'full_time', 'first_name' => 'Danilo',
            'middle_name' => null, 'last_name' => 'Villarino',
        ]);
        $partTime = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
            'employment_type' => 'industry_part_time', 'first_name' => 'Maria',
            'middle_name' => null, 'last_name' => 'Santos',
        ]);
        $unrelated = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active',
            'employment_type' => 'full_time', 'first_name' => 'Unrelated',
            'middle_name' => null, 'last_name' => 'Teacher',
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - North', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Lecture']);
        $subjects = collect([
            Subject::create([
                'course' => 'BSIT', 'code' => 'ITE 111', 'name' => 'Introduction to Computing',
                'subject_type' => 'Lecture', 'classification' => 'Major',
                'year_level' => 1, 'semester' => '1st', 'units' => 3,
            ]),
            Subject::create([
                'course' => 'BSIT', 'code' => 'GE 101', 'name' => 'Understanding the Self',
                'subject_type' => 'Lecture', 'classification' => 'Minor',
                'year_level' => 1, 'semester' => '1st', 'units' => 3,
            ]),
        ]);

        foreach ([[$fullTime, $subjects[0], 'M - W', '07:00'], [$partTime, $subjects[1], 'F - S', '08:30']] as [$instructor, $subject, $day, $start]) {
            ClassSchedule::create([
                'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
                'instructor_id' => $instructor->id, 'room_id' => $room->id,
                'academic_year' => '2026-2027', 'semester' => '1st', 'day' => $day,
                'start_time' => $start, 'end_time' => date('H:i', strtotime($start.' +90 minutes')),
            ]);
        }

        $this->actingAs($dean)->get(route('dean.print.index'))
            ->assertOk()
            ->assertSee('Summary of Teaching Loads')
            ->assertSee('Individual Faculty Load Sheet');

        $this->actingAs($dean)->get(route('dean.print.report', 'teaching-loads'))
            ->assertOk()
            ->assertSeeInOrder([
                'Madridejos Community College',
                'Information Technology Department',
                'First Semester, School Year 2026-2027',
                'Summary of Teaching Loads',
                'Full-Time Teachers',
                'Name of Teachers',
                'Subjects / Course',
                'Load',
                'Other Load',
                'Overload',
                'Total',
                'Danilo Villarino',
                'Introduction to Computing',
                'Part-Time Teachers',
                'Maria Santos',
                'Understanding the Self',
                'Prepared by:',
                'Dino Lopez Ilustrisimo',
                'Approved:',
                'Dr. Florpisa A. Montecillo, LPT',
            ])
            ->assertSee('Print Summary of Teaching Loads')
            ->assertDontSee('ITE 111')
            ->assertDontSee('GE 101')
            ->assertDontSee('(1 - North)')
            ->assertDontSee($unrelated->name)
            ->assertSee('images/mcc-college-logo.png', false)
            ->assertSee('images/bsit-department-logo.jpg', false);
    }

    public function test_dean_can_generate_a_scanner_compatible_qr_code_for_each_department_room(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);
        Room::create(['course' => 'BSBA', 'name' => 'BA 101', 'room_type' => 'Lecture']);

        $this->actingAs($dean)->get(route('dean.rooms.index'))
            ->assertOk()
            ->assertSee('Generate QR Code')
            ->assertSee('data-room-id="'.$room->id.'"', false)
            ->assertSee('data-room-name="Lab 1"', false)
            ->assertDontSee('data-room-name="BA 101"', false);
    }

    public function test_dean_import_pages_show_their_csv_template_download_button(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);

        foreach (['instructors', 'students', 'sections', 'subjects', 'rooms'] as $resource) {
            $this->actingAs($dean)->get(route("dean.{$resource}.index"))
                ->assertOk()
                ->assertSee('Download CSV Template')
                ->assertSee(route("dean.{$resource}.import-template"), false);
        }
    }

    public function test_room_usage_only_shows_schedules_for_enabled_semesters(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        Department::where('code', 'BSIT')->update([
            'semester_first_enabled' => false,
            'semester_second_enabled' => true,
            'semester_summer_enabled' => false,
        ]);
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - West', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);

        foreach (['1st' => 'ITE 111', '2nd' => 'ITE 211'] as $semester => $code) {
            $subject = Subject::create([
                'course' => 'BSIT', 'code' => $code, 'name' => $code,
                'subject_type' => 'Laboratory', 'classification' => 'Major',
                'year_level' => 1, 'semester' => $semester, 'units' => 3,
            ]);
            ClassSchedule::create([
                'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
                'instructor_id' => $instructor->id, 'room_id' => $room->id,
                'academic_year' => '2026-2027', 'semester' => $semester, 'day' => 'M - W',
                'start_time' => '08:30', 'end_time' => '10:00',
            ]);
        }

        $this->actingAs($dean)->get(route('dean.rooms.index'))
            ->assertOk()
            ->assertSee('ITE 211')
            ->assertDontSee('ITE 111');
    }

    public function test_dean_cannot_save_multiple_active_semesters(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $department = Department::where('code', 'BSIT')->firstOrFail();
        $department->update([
            'semester_first_enabled' => true,
            'semester_second_enabled' => false,
            'semester_summer_enabled' => false,
        ]);

        $this->actingAs($dean)->patch(route('dean.settings.semesters'), [
            'semester_first_enabled' => '1',
            'semester_second_enabled' => '1',
            'semester_summer_enabled' => '1',
        ])->assertRedirect()->assertSessionHasErrors('semester_availability');

        $department->refresh();
        $this->assertTrue($department->semester_first_enabled);
        $this->assertFalse($department->semester_second_enabled);
        $this->assertFalse($department->semester_summer_enabled);
    }

    public function test_dean_active_semester_selector_automatically_replaces_the_previous_semester(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);

        $this->actingAs($dean)->patch(route('dean.settings.semesters'), [
            'active_semester' => 'second',
        ])->assertRedirect();

        $department = Department::where('code', 'BSIT')->firstOrFail();
        $this->assertFalse($department->semester_first_enabled);
        $this->assertTrue($department->semester_second_enabled);
        $this->assertFalse($department->semester_summer_enabled);

        $this->actingAs($dean)->get(route('dean.dashboard'))
            ->assertOk()
            ->assertSee('type="radio" name="active_semester"', false)
            ->assertSee('Selecting a semester automatically turns the other semesters off.');
    }

    public function test_dean_instructor_edit_modal_omits_password_fields(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
        ]);

        $response = $this->actingAs($dean)
            ->get(route('dean.instructors.index', ['edit' => $instructor->id]))
            ->assertOk();

        $instructorForm = str($response->getContent())
            ->after('<form id="instructorCreateForm"')
            ->before('</form>')
            ->toString();

        $this->assertStringNotContainsString('name="password"', $instructorForm);
        $this->assertStringNotContainsString('name="password_confirmation"', $instructorForm);
    }

    public function test_dean_can_delete_a_room_without_deleting_its_active_or_archived_schedules(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - West', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 111', 'name' => 'Introduction to Computing',
            'subject_type' => 'Laboratory', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);
        $scheduleData = [
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st',
            'start_time' => '08:30', 'end_time' => '10:00',
        ];
        $activeSchedule = ClassSchedule::create([...$scheduleData, 'day' => 'M - W']);
        $archivedSchedule = ClassSchedule::create([...$scheduleData, 'day' => 'T - Th']);
        $archivedSchedule->delete();

        $this->actingAs($dean)->delete(route('dean.rooms.destroy', $room))
            ->assertRedirect()
            ->assertSessionHas('success', 'Room deleted successfully.');

        $this->assertModelMissing($room);
        $this->assertDatabaseHas('class_schedules', ['id' => $activeSchedule->id, 'room_id' => null, 'deleted_at' => null]);
        $this->assertSoftDeleted('class_schedules', ['id' => $archivedSchedule->id, 'room_id' => null]);
    }

    public function test_dean_can_remove_all_department_subjects_rooms_and_sections(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);

        $deanSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 111', 'name' => 'Introduction to Computing',
            'subject_type' => 'Laboratory', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);
        $gecSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'GE 101', 'name' => 'Understanding the Self',
            'subject_type' => 'Lecture', 'classification' => 'Minor', 'managed_by_gec' => true,
            'year_level' => 1, 'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);
        $otherSubject = Subject::create([
            'course' => 'BSBA', 'code' => 'BA 101', 'name' => 'Business Fundamentals',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);

        $deanRoom = Room::create(['course' => 'BSIT', 'name' => 'ITE 101', 'room_type' => 'Laboratory']);
        $otherRoom = Room::create(['course' => 'BSBA', 'name' => 'BA 101', 'room_type' => 'Lecture']);

        $deanSection = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $otherSection = AcademicSection::create([
            'course' => 'BSBA', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        $this->actingAs($dean)->get(route('dean.subjects.index'))
            ->assertOk()
            ->assertSee('Delete All Subjects')
            ->assertSee(route('dean.subjects.destroy-all'), false);
        $this->actingAs($dean)->delete(route('dean.subjects.destroy-all'))
            ->assertRedirect(route('dean.subjects.index'));
        $this->assertModelMissing($deanSubject);
        $this->assertModelExists($gecSubject);
        $this->assertModelExists($otherSubject);

        $this->actingAs($dean)->get(route('dean.rooms.index'))
            ->assertOk()
            ->assertSee('Delete All Rooms')
            ->assertSee(route('dean.rooms.destroy-all'), false);
        $this->actingAs($dean)->delete(route('dean.rooms.destroy-all'))
            ->assertRedirect(route('dean.rooms.index'));
        $this->assertModelMissing($deanRoom);
        $this->assertModelExists($otherRoom);

        $this->actingAs($dean)->get(route('dean.sections.index'))
            ->assertOk()
            ->assertSee('Delete All Sections')
            ->assertSee(route('dean.sections.destroy-all'), false);
        $this->actingAs($dean)->delete(route('dean.sections.destroy-all'))
            ->assertRedirect(route('dean.sections.index'));
        $this->assertModelMissing($deanSection);
        $this->assertModelExists($otherSection);
    }

    public function test_deleted_class_schedule_moves_to_archive_and_can_be_restored(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'employment_type' => null, 'account_status' => 'active',
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section 1', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'IT201', 'name' => 'Data Structures',
            'subject_type' => 'Laboratory', 'classification' => 'Major',
            'year_level' => 2, 'semester' => '1st', 'units' => 3,
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);
        $schedule = ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => 'M - W',
            'start_time' => '07:30', 'end_time' => '10:00',
        ]);

        $this->actingAs($dean)
            ->delete(route('dean.timetable.destroy', $schedule))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('class_schedules', ['id' => $schedule->id]);
        $deletedOn = ClassSchedule::onlyTrashed()->findOrFail($schedule->id)->deleted_at->toDateString();
        $this->actingAs($dean)->get(route('dean.archive.index'))
            ->assertOk()
            ->assertSee('data-archive-date="'.$deletedOn.'"', false)
            ->assertSee('data-archive-period="'.$deletedOn.'-2026-2027-1st"', false)
            ->assertSee('data-archive-section="'.$section->id.'-'.$deletedOn.'-2026-2027-1st"', false)
            ->assertSeeInOrder(['Archive', 'Deleted on', 'Academic Year 2026-2027', '1st Semester', 'Section 1', 'Restore Schedule', 'Delete Schedule', 'IT201', 'Choose'])
            ->assertSee('Delete Schedule Permanently?');

        $this->actingAs($dean)
            ->patch(route('dean.archive.restore', $schedule->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted('class_schedules', ['id' => $schedule->id]);
        $this->assertSame(1, ClassSchedule::whereKey($schedule->id)->count());
    }

    public function test_deleted_instructor_account_appears_on_archive_page_and_can_be_restored(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);

        $this->actingAs($dean)->delete(route('dean.instructors.destroy', $instructor))->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $instructor->id]);

        $this->actingAs($dean)->get(route('dean.archive.index'))
            ->assertOk()
            ->assertSee('Deleted Instructor Accounts')
            ->assertSee($instructor->email);

        $this->actingAs($dean)
            ->patch(route('dean.archive.accounts.restore', $instructor->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $instructor->id, 'deleted_at' => null]);
    }

    public function test_deleted_instructor_account_can_be_permanently_deleted_from_archive_page(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $this->actingAs($dean)->delete(route('dean.instructors.destroy', $instructor))->assertRedirect();

        $this->actingAs($dean)
            ->delete(route('dean.archive.accounts.destroy', $instructor->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $instructor->id]);
    }

    public function test_dean_can_permanently_delete_all_deleted_instructor_accounts_at_once(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructorOne = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $instructorTwo = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $otherDeptInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active']);
        $this->actingAs($dean)->delete(route('dean.instructors.destroy', $instructorOne))->assertRedirect();
        $this->actingAs($dean)->delete(route('dean.instructors.destroy', $instructorTwo))->assertRedirect();
        $otherDeptInstructor->delete();

        $this->actingAs($dean)->get(route('dean.archive.index'))
            ->assertOk()
            ->assertSee('Delete All Permanently');

        $this->actingAs($dean)
            ->delete(route('dean.archive.accounts.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $instructorOne->id]);
        $this->assertDatabaseMissing('users', ['id' => $instructorTwo->id]);
        // Untouched: belongs to a different department.
        $this->assertSoftDeleted('users', ['id' => $otherDeptInstructor->id]);
    }

    public function test_deleting_all_instructor_archive_accounts_with_none_present_shows_an_error(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);

        $this->actingAs($dean)
            ->delete(route('dean.archive.accounts.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_dean_cannot_restore_or_delete_another_departments_deleted_instructor(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $otherInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active']);
        $otherInstructor->delete();

        $this->actingAs($dean)->patch(route('dean.archive.accounts.restore', $otherInstructor->id))->assertNotFound();
        $this->actingAs($dean)->delete(route('dean.archive.accounts.destroy', $otherInstructor->id))->assertNotFound();
        $this->assertSoftDeleted('users', ['id' => $otherInstructor->id]);
    }

    public function test_deleted_student_account_appears_on_archive_page_and_can_be_restored(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $student = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'account_status' => 'active']);

        $this->actingAs($dean)->delete(route('dean.students.destroy', $student))->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $student->id]);

        $this->actingAs($dean)->get(route('dean.archive.index'))
            ->assertOk()
            ->assertSee('Deleted Student Accounts')
            ->assertSee($student->email);

        $this->actingAs($dean)
            ->patch(route('dean.archive.students.restore', $student->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'deleted_at' => null]);
    }

    public function test_deleted_student_account_can_be_permanently_deleted_from_archive_page(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $student = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'account_status' => 'active']);
        $this->actingAs($dean)->delete(route('dean.students.destroy', $student))->assertRedirect();

        $this->actingAs($dean)
            ->delete(route('dean.archive.students.destroy', $student->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }

    public function test_dean_can_permanently_delete_all_deleted_student_accounts_at_once(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $studentOne = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'account_status' => 'active']);
        $studentTwo = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'account_status' => 'active']);
        $otherDeptStudent = User::factory()->create(['role' => 'student', 'course' => 'BSBA', 'account_status' => 'active']);
        $this->actingAs($dean)->delete(route('dean.students.destroy', $studentOne))->assertRedirect();
        $this->actingAs($dean)->delete(route('dean.students.destroy', $studentTwo))->assertRedirect();
        $otherDeptStudent->delete();

        $this->actingAs($dean)->get(route('dean.archive.index'))
            ->assertOk()
            ->assertSee('Delete All Permanently');

        $this->actingAs($dean)
            ->delete(route('dean.archive.students.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $studentOne->id]);
        $this->assertDatabaseMissing('users', ['id' => $studentTwo->id]);
        $this->assertSoftDeleted('users', ['id' => $otherDeptStudent->id]);
    }

    public function test_deleting_all_student_archive_accounts_with_none_present_shows_an_error(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);

        $this->actingAs($dean)
            ->delete(route('dean.archive.students.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_dean_cannot_restore_or_delete_another_departments_deleted_student(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $otherStudent = User::factory()->create(['role' => 'student', 'course' => 'BSBA', 'account_status' => 'active']);
        $otherStudent->delete();

        $this->actingAs($dean)->patch(route('dean.archive.students.restore', $otherStudent->id))->assertNotFound();
        $this->actingAs($dean)->delete(route('dean.archive.students.destroy', $otherStudent->id))->assertNotFound();
        $this->assertSoftDeleted('users', ['id' => $otherStudent->id]);
    }

    public function test_permanent_archive_deletion_uses_confirmation_and_success_notification(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section North', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'IT101', 'name' => 'Computing Fundamentals',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);
        $schedule = ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => 'M - W',
            'start_time' => '07:00', 'end_time' => '08:30',
        ]);
        $otherPeriodSchedule = ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2027-2028', 'semester' => '2nd', 'day' => 'T - Th',
            'start_time' => '09:00', 'end_time' => '10:30',
        ]);
        $schedule->delete();
        $otherPeriodSchedule->delete();
        $deletedOn = ClassSchedule::onlyTrashed()->findOrFail($schedule->id)->deleted_at->toDateString();

        $this->actingAs($dean)->get(route('dean.archive.index'))
            ->assertOk()
            ->assertSee('Delete Schedule Permanently?')
            ->assertSee('Academic Year 2027-2028')
            ->assertSee('Academic Year 2026-2027')
            ->assertSee(route('dean.archive.sections.destroy', [
                'section' => $section,
                'academic_year' => '2026-2027',
                'semester' => '1st',
                'deleted_on' => $deletedOn,
            ]));

        $this->actingAs($dean)->delete(route('dean.archive.sections.destroy', [
            'section' => $section,
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'deleted_on' => $deletedOn,
        ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('class_schedules', ['id' => $schedule->id]);
        $this->assertSoftDeleted('class_schedules', ['id' => $otherPeriodSchedule->id]);
        $this->actingAs($dean)->get(route('dean.archive.index'))
            ->assertOk()
            ->assertSee('id="deanNotice"', false)
            ->assertSee('Section North 2026-2027 1st Semester archive from '.date('F j, Y', strtotime($deletedOn)).' permanently deleted');
    }

    public function test_archive_can_be_filtered_by_academic_year_and_semester(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $firstSemesterSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'IT101', 'name' => 'Computing Fundamentals',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $secondSemesterSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'IT102', 'name' => 'Computer Programming',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '2nd', 'units' => 3,
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);

        foreach ([
            [$firstSemesterSubject, '2026-2027', '1st', 'M - W'],
            [$secondSemesterSubject, '2027-2028', '2nd', 'T - Th'],
        ] as [$subject, $academicYear, $semester, $day]) {
            $schedule = ClassSchedule::create([
                'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
                'instructor_id' => $instructor->id, 'room_id' => $room->id,
                'academic_year' => $academicYear, 'semester' => $semester, 'day' => $day,
                'start_time' => '07:00', 'end_time' => '08:30',
            ]);
            $schedule->delete();
        }

        $this->actingAs($dean)->get(route('dean.archive.index', [
            'academic_year' => '2026-2027',
            'semester' => '1st',
        ]))
            ->assertOk()
            ->assertSee('name="academic_year"', false)
            ->assertDontSee('name="semester"', false)
            ->assertSee('name="deleted_on"', false)
            ->assertSee('Academic Year 2026-2027')
            ->assertSee('1st Semester archived schedules')
            ->assertSee('IT101')
            ->assertDontSee('IT102')
            ->assertDontSee('Academic Year 2027-2028')
            ->assertSee('data-auto-filter', false)
            ->assertDontSee('>Clear<', false);
    }

    public function test_archive_is_classified_and_filterable_by_deletion_date(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section Date', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);
        $subjects = collect([
            ['code' => 'IT111', 'name' => 'First Archived Subject'],
            ['code' => 'IT112', 'name' => 'Second Archived Subject'],
        ])->map(fn (array $subject) => Subject::create([
            'course' => 'BSIT', 'code' => $subject['code'], 'name' => $subject['name'],
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]));

        foreach ($subjects as $index => $subject) {
            $schedule = ClassSchedule::create([
                'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
                'instructor_id' => $instructor->id, 'room_id' => $room->id,
                'academic_year' => '2026-2027', 'semester' => '1st', 'day' => $index === 0 ? 'M - W' : 'T - Th',
                'start_time' => '07:00', 'end_time' => '08:30',
            ]);
            $schedule->delete();
            ClassSchedule::onlyTrashed()->whereKey($schedule->id)->update([
                'deleted_at' => $index === 0 ? '2026-07-20 09:15:00' : '2026-07-22 14:30:00',
            ]);
        }

        $this->actingAs($dean)->get(route('dean.archive.index'))
            ->assertOk()
            ->assertSeeInOrder(['Deleted on July 22, 2026', 'Second Archived Subject', 'Deleted on July 20, 2026', 'First Archived Subject'])
            ->assertSee('data-archive-date="2026-07-22"', false)
            ->assertSee('data-archive-date="2026-07-20"', false);

        $this->actingAs($dean)->get(route('dean.archive.index', ['deleted_on' => '2026-07-20']))
            ->assertOk()
            ->assertSee('First Archived Subject')
            ->assertDontSee('Second Archived Subject')
            ->assertSee('value="2026-07-20" selected', false);
    }

    public function test_dean_can_only_access_records_from_their_department(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'year_level' => 2]);
        $otherSection = AcademicSection::create([
            'course' => 'BSBA', 'name' => 'Year 1-A', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => '1st',
        ]);

        $this->actingAs($dean)->get(route('dean.dashboard'))
            ->assertOk()
            ->assertSee('BSIT Dean Dashboard')
            ->assertSee('grid-template-columns:repeat(auto-fit,minmax(210px,1fr))', false)
            ->assertViewHas('analytics', fn (array $analytics): bool => $analytics['students']['Year 2'] === 1);
        $this->actingAs($dean)->get(route('dean.sections.edit', $otherSection))->assertNotFound();
    }

    public function test_dean_dashboard_only_filters_subjects_and_recent_schedules_by_the_active_semester(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        Department::where('code', 'BSIT')->firstOrFail()->update([
            'semester_first_enabled' => true,
            'semester_second_enabled' => false,
            'semester_summer_enabled' => false,
        ]);
        $firstSection = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - First', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => '1st',
        ]);
        $secondSection = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - Second', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => '2nd',
        ]);
        User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'academic_section_id' => $firstSection->id]);
        User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'academic_section_id' => $secondSection->id]);
        $firstInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $secondInstructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $firstSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 101', 'name' => 'First Semester Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $secondSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 201', 'name' => 'Second Semester Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 1, 'semester' => '2nd', 'units' => 3,
        ]);
        $firstRoom = Room::create(['course' => 'BSIT', 'name' => 'First Room', 'room_type' => 'Lecture']);
        $secondRoom = Room::create(['course' => 'BSIT', 'name' => 'Second Room', 'room_type' => 'Laboratory']);
        ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $firstSection->id, 'subject_id' => $firstSubject->id,
            'instructor_id' => $firstInstructor->id, 'room_id' => $firstRoom->id,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => 'M - W', 'start_time' => '08:30', 'end_time' => '10:00',
        ]);
        ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $secondSection->id, 'subject_id' => $secondSubject->id,
            'instructor_id' => $secondInstructor->id, 'room_id' => $secondRoom->id,
            'academic_year' => '2026-2027', 'semester' => '2nd', 'day' => 'M - W', 'start_time' => '08:30', 'end_time' => '10:00',
        ]);

        $this->actingAs($dean)->get(route('dean.dashboard'))
            ->assertOk()
            ->assertSee('Subjects and recent schedules reflect the active 1st Semester')
            ->assertSee('ITE 101')
            ->assertDontSee('ITE 201')
            ->assertViewHas('statistics', fn (array $statistics): bool => $statistics === [
                'instructors' => 2,
                'students' => 2,
                'subjects' => 1,
                'sections' => 2,
                'rooms' => 2,
            ])
            ->assertViewHas('analytics', fn (array $analytics): bool => $analytics['rooms'] === [
                'Laboratory' => 1,
                'Lecture' => 1,
            ] && ! array_key_exists('Unassigned', $analytics['subjects'])
                && ! array_key_exists('Unassigned', $analytics['sections']));
    }

    public function test_dean_subject_deletion_only_affects_the_active_semester(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        Department::where('code', 'BSIT')->firstOrFail()->update([
            'semester_first_enabled' => true,
            'semester_second_enabled' => false,
            'semester_summer_enabled' => false,
        ]);
        $firstSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 101', 'name' => 'First Semester Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $secondSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 201', 'name' => 'Second Semester Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 1, 'semester' => '2nd', 'units' => 3,
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - Alpha', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Lecture 1', 'room_type' => 'Lecture']);
        ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $firstSubject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => 'M - W', 'start_time' => '08:30', 'end_time' => '10:00',
        ]);
        $secondSchedule = ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $secondSubject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '2nd', 'day' => 'M - W', 'start_time' => '08:30', 'end_time' => '10:00',
        ]);

        $this->actingAs($dean)->delete(route('dean.subjects.destroy-all'))
            ->assertRedirect(route('dean.subjects.index'));

        $this->assertModelMissing($firstSubject);
        $this->assertModelExists($secondSubject);
        $this->assertDatabaseMissing('class_schedules', ['subject_id' => $firstSubject->id]);
        $this->assertDatabaseHas('class_schedules', ['id' => $secondSchedule->id]);
        $this->actingAs($dean)->delete(route('dean.subjects.destroy', $secondSubject))->assertNotFound();
    }

    public function test_dean_can_adjust_department_instructor_unit_limits(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'account_status' => 'active',
            'employment_type' => 'full_time',
            'first_name' => 'Emily',
            'last_name' => 'Ilustrisimo',
        ]);
        $otherDepartmentInstructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSBA',
            'account_status' => 'active',
            'employment_type' => 'full_time',
        ]);

        $this->actingAs($dean)
            ->get(route('dean.instructor-units.index'))
            ->assertOk()
            ->assertSee('Instructor Unit Management')
            ->assertSee('Emily')
            ->assertDontSee('Reset to Default')
            ->assertDontSee($otherDepartmentInstructor->email);

        $this->actingAs($dean)
            ->patch(route('dean.instructor-units.update', $instructor), [
                'teaching_unit_limit' => 36,
                'unit_limit_note' => 'Increased after an excellent performance review.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $instructor->refresh();
        $this->assertSame(36, $instructor->teaching_unit_limit);
        $this->assertSame([0, 36], app(ClassScheduleGenerator::class)->workloadRange($instructor));
        $this->assertNotNull($instructor->unit_limit_updated_at);

        $this->actingAs($dean)
            ->patch(route('dean.instructor-units.update', $otherDepartmentInstructor), [
                'teaching_unit_limit' => 40,
            ])
            ->assertNotFound();
    }

    public function test_instructor_units_warns_when_major_subject_units_exceed_active_instructor_capacity(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        Department::where('code', 'BSIT')->firstOrFail()->update([
            'semester_first_enabled' => true,
            'semester_second_enabled' => false,
            'semester_summer_enabled' => false,
            'default_unit_limit_full_time' => 30,
        ]);
        User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'account_status' => 'active',
            'employment_type' => 'full_time',
            'teaching_unit_limit' => 30,
        ]);

        foreach (range(1, 11) as $number) {
            Subject::create([
                'course' => 'BSIT',
                'code' => "ITE {$number}",
                'name' => "Subject ITE {$number}",
                'subject_type' => 'Lecture',
                'classification' => 'Major',
                'year_level' => 1,
                'semester' => '1st',
                'curriculum' => 'New',
                'units' => 3,
            ]);
        }

        $this->actingAs($dean)->get(route('dean.instructor-units.index'))
            ->assertOk()
            ->assertSee('Instructor capacity shortage')
            ->assertSee('33 workload hours')
            ->assertSee('30 workload hours')
            ->assertSee('shortfall is')
            ->assertSee('3 workload hours')
            ->assertSee('1 additional full-time instructor');
    }

    public function test_automatic_schedule_generation_creates_conflict_free_department_schedule(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'employment_type' => null, 'account_status' => 'active',
        ]);
        Subject::create([
            'course' => 'BSIT', 'code' => 'IT101', 'name' => 'Introduction to Computing',
            'subject_type' => 'Lecture', 'year_level' => 2, 'semester' => '1st', 'units' => 3,
            'instructor_id' => $instructor->id,
        ]);
        Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'capacity' => 40]);
        $sections = collect(['Section 1', 'Section 2', 'Section 3'])->map(fn (string $name) => AcademicSection::create([
            'course' => 'BSIT', 'name' => $name, 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]));

        $response = $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => 2,
            'number_of_sections' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('academic_sections', 3);
        $this->assertDatabaseCount('class_schedules', 2);
        $this->assertSame(2, ClassSchedule::distinct()->count('section_id'));
        $this->assertEqualsCanonicalizing(
            $sections->take(2)->pluck('id')->all(),
            ClassSchedule::distinct()->pluck('section_id')->all(),
        );

        $this->actingAs($dean)
            ->get(route('dean.timetable.index'))
            ->assertOk()
            ->assertViewHas('sectionPages', fn ($sectionPages): bool => $sectionPages->count() === 2)
            ->assertViewHas('schedulesBySection', fn ($schedules): bool => $schedules->count() === 2)
            ->assertSeeInOrder(['Time', 'Days', 'Subject Code', 'Subject Description', 'Unit', 'Room', 'Instructors'])
            ->assertDontSee('<th>Period</th>', false)
            ->assertSee('Which Major class entry do you want to edit?', false)
            ->assertSee('id="sendSchedulesToGecModal"', false)
            ->assertSee('Send Schedules to GEC?', false)
            ->assertDontSee('Send all class schedules to GEC?', false)
            ->assertSee('Delete Major Schedules for This Section?');

        $this->actingAs($dean)
            ->delete(route('dean.timetable.sections.destroy', $sections->first()))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('class_schedules', ['section_id' => $sections->first()->id]);
        $this->assertSame(0, ClassSchedule::where('section_id', $sections->first()->id)->count());
        $this->assertDatabaseHas('class_schedules', ['section_id' => $sections->get(1)->id]);
    }

    public function test_dean_can_archive_all_timetable_schedules_matching_the_current_filters(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $bsitInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
        ]);
        $bsbaInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active',
        ]);
        $bsitSection = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - North', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $bsbaSection = AcademicSection::create([
            'course' => 'BSBA', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $bsitSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 111', 'name' => 'Introduction in Computing',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 1,
            'semester' => '1st', 'units' => 3, 'instructor_id' => $bsitInstructor->id,
        ]);
        $bsbaSubject = Subject::create([
            'course' => 'BSBA', 'code' => 'BA 111', 'name' => 'Business Fundamentals',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 1,
            'semester' => '1st', 'units' => 3, 'instructor_id' => $bsbaInstructor->id,
        ]);

        $matchingSchedule = ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $bsitSection->id, 'subject_id' => $bsitSubject->id,
            'instructor_id' => $bsitInstructor->id, 'academic_year' => '2026-2027', 'semester' => '1st',
            'day' => 'M - W', 'start_time' => '07:00', 'end_time' => '09:30',
        ]);
        $otherSemester = ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $bsitSection->id, 'subject_id' => $bsitSubject->id,
            'instructor_id' => $bsitInstructor->id, 'academic_year' => '2026-2027', 'semester' => '2nd',
            'day' => 'T - Th', 'start_time' => '07:00', 'end_time' => '09:30',
        ]);
        $otherDepartment = ClassSchedule::create([
            'course' => 'BSBA', 'section_id' => $bsbaSection->id, 'subject_id' => $bsbaSubject->id,
            'instructor_id' => $bsbaInstructor->id, 'academic_year' => '2026-2027', 'semester' => '1st',
            'day' => 'M - W', 'start_time' => '07:00', 'end_time' => '09:30',
        ]);

        $this->actingAs($dean)
            ->get(route('dean.timetable.index'))
            ->assertOk()
            ->assertSee('Delete All Major Schedules');

        $this->actingAs($dean)
            ->delete(route('dean.timetable.destroy-all', [
                'academic_year' => '2026-2027',
                'semester' => '1st',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('class_schedules', ['id' => $matchingSchedule->id]);
        $this->assertDatabaseHas('class_schedules', ['id' => $otherSemester->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('class_schedules', ['id' => $otherDepartment->id, 'deleted_at' => null]);
    }

    public function test_dean_schedule_deletion_preserves_gec_minor_subject_schedules(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $majorInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
        ]);
        $gecInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'GEC', 'account_status' => 'active',
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - Protected', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $majorSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 120', 'name' => 'Major Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 1,
            'semester' => '1st', 'units' => 3,
        ]);
        $minorSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'GE 120', 'name' => 'GEC Minor Subject',
            'subject_type' => 'Lecture', 'classification' => 'Minor', 'managed_by_gec' => true,
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $majorSchedule = ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $majorSubject->id,
            'instructor_id' => $majorInstructor->id, 'academic_year' => '2026-2027', 'semester' => '1st',
            'day' => 'M - W', 'start_time' => '07:00', 'end_time' => '09:30',
        ]);
        $minorSchedule = ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $minorSubject->id,
            'instructor_id' => $gecInstructor->id, 'academic_year' => '2026-2027', 'semester' => '1st',
            'day' => 'T - Th', 'start_time' => '07:00', 'end_time' => '09:30',
        ]);

        $this->actingAs($dean)
            ->get(route('dean.timetable.index'))
            ->assertOk()
            ->assertSee('GEC Minor Subject')
            ->assertSee('GEC Minor')
            ->assertSee('Managed by GEC');

        $this->actingAs($dean)
            ->delete(route('dean.timetable.destroy-all', [
                'academic_year' => '2026-2027',
                'semester' => '1st',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('class_schedules', ['id' => $majorSchedule->id]);
        $this->assertDatabaseHas('class_schedules', ['id' => $minorSchedule->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('subjects', ['id' => $minorSubject->id, 'classification' => 'Minor']);

        $this->actingAs($dean)
            ->delete(route('dean.timetable.destroy', $minorSchedule))
            ->assertNotFound();

        $this->assertDatabaseHas('class_schedules', ['id' => $minorSchedule->id, 'deleted_at' => null]);
    }

    public function test_dean_can_generate_schedules_for_all_available_year_levels(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->count(3)->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => null,
            'account_status' => 'active',
        ]);
        Room::create([
            'course' => 'BSIT',
            'name' => 'Room 101',
            'room_type' => 'Lecture',
        ]);

        foreach ([1, 2] as $yearLevel) {
            AcademicSection::create([
                'course' => 'BSIT',
                'name' => "{$yearLevel} - North",
                'year_level' => $yearLevel,
                'academic_year' => '2026-2027',
                'semester' => 'All',
            ]);
        }
        AcademicSection::create([
            'course' => 'BSIT',
            'name' => '2 - South',
            'year_level' => 2,
            'academic_year' => '2026-2027',
            'semester' => 'All',
        ]);

        foreach ([
            ['IT101', 'First Year Major A', 1, 'Major'],
            ['IT102', 'First Year Major B', 1, 'Major'],
            // A third Major subject so the First Year section can cover all
            // three meeting-day pairs, since Minor subjects are no longer
            // generated.
            ['IT103', 'First Year Major C', 1, 'Major'],
            ['GE101', 'First Year Minor', 1, 'Minor'],
            ['IT201', 'Second Year Major', 2, 'Major'],
        ] as [$code, $name, $yearLevel, $classification]) {
            Subject::create([
                'course' => 'BSIT',
                'code' => $code,
                'name' => $name,
                'subject_type' => 'Lecture',
                'classification' => $classification,
                'year_level' => $yearLevel,
                'semester' => '1st',
                'units' => 3,
            ]);
        }

        $this->actingAs($dean)
            ->get(route('dean.schedules.create'))
            ->assertOk()
            ->assertSee('value="all"', false)
            ->assertSee('All Year Levels');

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'year_levels' => ['1', '2'],
            'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $schedules = ClassSchedule::with(['section', 'subject'])->get();
        $this->assertCount(5, $schedules);
        $this->assertSame(3, $schedules->pluck('section_id')->unique()->count());
        $this->assertSame([1, 2], $schedules->pluck('section.year_level')->unique()->sort()->values()->all());
        $this->assertTrue(
            $schedules->every(
                fn (ClassSchedule $schedule): bool => $schedule->section->year_level === $schedule->subject->year_level,
            ),
        );
        $this->assertFalse($schedules->pluck('subject.code')->contains('GE101'), 'Minor subjects must never be auto-generated.');
    }

    public function test_bsit_major_schedules_use_tba_after_laboratory_slots_are_full(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->count(3)->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => null,
            'account_status' => 'active',
        ]);
        Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);

        foreach (range(1, 2) as $sectionNumber) {
            AcademicSection::create([
                'course' => 'BSIT',
                'name' => "2 - Section {$sectionNumber}",
                'year_level' => 2,
                'academic_year' => '2026-2027',
                'semester' => 'All',
            ]);
        }

        foreach (['ITE 111', 'ITE 112', 'ITE 211', 'ITE 212', 'ITE 213'] as $subjectCode) {
            Subject::create([
                'course' => 'BSIT',
                'code' => $subjectCode,
                'name' => "Priority Major {$subjectCode}",
                'classification' => 'Major',
                'subject_type' => 'Laboratory',
                'year_level' => 2,
                'semester' => '1st',
                'units' => 3,
            ]);
        }

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'year_level' => '2',
            'number_of_sections' => 2,
        ])->assertRedirect()->assertSessionHas('success');

        // The selected coverage section uses all three day pairs. The other
        // Year 2 section uses weekday pairs, leaving only two real weekday
        // Lab slots after the coverage section is scheduled.
        $this->assertSame(7, ClassSchedule::whereNotNull('room_id')->count());
        $this->assertSame(3, ClassSchedule::whereNull('room_id')->count());
    }

    public function test_priority_laboratory_subjects_receive_rooms_before_non_priority_laboratories(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructors = User::factory()->count(3)->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => null,
            'account_status' => 'active',
        ]);
        Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);

        foreach (range(1, 4) as $sectionNumber) {
            AcademicSection::create([
                'course' => 'BSIT',
                'name' => "2 - Section {$sectionNumber}",
                'year_level' => 2,
                'academic_year' => '2026-2027',
                'semester' => 'All',
            ]);
        }

        $subjects = collect([
            ['ITE 211', 'Priority Programming', $instructors[0]],
            ['ITE 212', 'Priority Graphics', $instructors[1]],
            ['ITE 215', 'Remaining Laboratory', $instructors[2]],
        ])->map(function (array $details): Subject {
            $subject = Subject::create([
                'course' => 'BSIT',
                'code' => $details[0],
                'name' => $details[1],
                'classification' => 'Major',
                'subject_type' => 'Laboratory',
                'year_level' => 2,
                'semester' => '1st',
                'units' => 3,
            ]);
            $subject->instructors()->attach($details[2]);

            return $subject;
        });

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'year_level' => '2',
            'number_of_sections' => 4,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(
            8,
            ClassSchedule::whereIn('subject_id', $subjects->take(2)->pluck('id'))->whereNotNull('room_id')->count(),
        );
        // The priority subjects consume 8 of the room's 9 weekly slots (3
        // fixed blocks x 3 day pairs), leaving only 1 slot for the 4
        // non-priority requests; the other 3 fall back to TBA.
        $this->assertSame(
            3,
            ClassSchedule::where('subject_id', $subjects[2]->id)->whereNull('room_id')->count(),
        );
    }

    public function test_generator_applies_day_time_duration_room_and_industry_instructor_rules(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $industryInstructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => 'industry_part_time',
            'outside_work_end_time' => '13:30',
            'account_status' => 'active',
        ]);
        $lectureRoom = Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);
        $laboratoryRoom = Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);
        AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section 1', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        // Three Major subjects so the First Year section can cover all three
        // meeting-day pairs, since Minor subjects are no longer generated.
        foreach ([
            ['IT101', 'Programming 1', 'Lecture'],
            ['IT102', 'Programming 2', 'Laboratory'],
            ['IT103', 'Industry Internship', 'Internship'],
        ] as [$code, $name, $type]) {
            $subject = Subject::create([
                'course' => 'BSIT', 'code' => $code, 'name' => $name,
                'subject_type' => $type, 'classification' => 'Major',
                'year_level' => 1, 'semester' => '1st', 'units' => 3,
            ]);
            $subject->instructors()->attach($industryInstructor);
        }

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => 1,
            'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $schedules = ClassSchedule::with(['subject', 'room'])->get();
        $this->assertCount(3, $schedules);
        $this->assertEqualsCanonicalizing(['M - W', 'T - Th', 'F - S'], $schedules->pluck('day')->unique()->all());
        $this->assertSame([0, 15], app(ClassScheduleGenerator::class)->workloadRange($industryInstructor));

        foreach ($schedules as $schedule) {
            $start = substr($schedule->start_time, 0, 5);
            $end = substr($schedule->end_time, 0, 5);
            $this->assertGreaterThanOrEqual('07:00', $start);
            $this->assertLessThanOrEqual('19:00', $end);
            $durationMinutes = (int) ((strtotime($end) - strtotime($start)) / 60);
            $this->assertSame([
                'IT101' => 90,
                'IT102' => 150,
                'IT103' => 180,
            ][$schedule->subject->code], $durationMinutes);

            $this->assertContains($schedule->day, ['M - W', 'T - Th', 'F - S']);
            $this->assertSame($industryInstructor->id, $schedule->instructor_id);
            // The instructor's 1:30 PM outside-work-hours cutoff only leaves
            // the 2:00 PM onward BSIT blocks open on weekdays and Friday.
            $this->assertSame('14:00', $start);

            if ($schedule->subject->subject_type === 'Lecture') {
                $this->assertSame($lectureRoom->id, $schedule->room_id);
            } elseif ($schedule->subject->subject_type === 'Laboratory') {
                $this->assertSame($laboratoryRoom->id, $schedule->room_id);
            } else {
                $this->assertNull($schedule->room_id);
            }
        }

        $industryUnits = $schedules->where('instructor_id', $industryInstructor->id)->sum(fn (ClassSchedule $schedule): float => (float) $schedule->subject->units);
        $this->assertLessThanOrEqual(15, $industryUnits);
    }

    public function test_second_to_fourth_year_major_subjects_are_balanced_through_friday_and_saturday(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'employment_type' => null, 'account_status' => 'active',
        ]);
        Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);
        AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section 1', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        foreach (range(1, 4) as $number) {
            Subject::create([
                'course' => 'BSIT', 'code' => "IT20{$number}", 'name' => "Major Subject {$number}",
                'subject_type' => 'Lecture', 'classification' => 'Major',
                'year_level' => 2, 'semester' => '1st', 'units' => 3,
            ]);
        }

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => 2,
            'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $dayCounts = ClassSchedule::query()->selectRaw('day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day');
        $this->assertEqualsCanonicalizing(['M - W', 'T - Th', 'F - S'], $dayCounts->keys()->all());
        $this->assertSame(4, $dayCounts->sum());
        $this->assertSame(1, (int) $dayCounts->min(), 'every meeting pattern should receive at least one of the four subjects');
        $this->assertSame(2, (int) $dayCounts->max(), 'four subjects across three patterns should leave exactly one pattern with two classes');
    }

    public function test_schedule_generation_uses_only_the_selected_curriculum(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'employment_type' => 'full_time', 'account_status' => 'active',
        ]);
        Room::create(['course' => 'BSBA', 'name' => 'BA 101', 'room_type' => 'Lecture']);
        AcademicSection::create([
            'course' => 'BSBA', 'name' => '2 - North', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        $oldSubject = Subject::create([
            'course' => 'BSBA', 'code' => 'BA OLD', 'name' => 'Old Curriculum Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 2,
            'semester' => '1st', 'curriculum' => 'Old', 'units' => 3,
        ]);
        $newSubject = Subject::create([
            'course' => 'BSBA', 'code' => 'BA NEW', 'name' => 'New Curriculum Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 2,
            'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);
        $oldSubject->instructors()->attach($instructor);
        $newSubject->instructors()->attach($instructor);

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'curriculum' => 'New',
            'year_level' => '2', 'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('class_schedules', ['subject_id' => $newSubject->id]);
        $this->assertDatabaseMissing('class_schedules', ['subject_id' => $oldSubject->id]);
    }

    public function test_first_and_second_year_sections_receive_priority_for_room_periods(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'employment_type' => null, 'account_status' => 'active',
        ]);
        $room = Room::create(['course' => 'BSBA', 'name' => 'BA 101', 'room_type' => 'Lecture']);

        $secondYear = AcademicSection::create([
            'course' => 'BSBA', 'name' => '2 - East', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $fourthYear = AcademicSection::create([
            'course' => 'BSBA', 'name' => '4 - East', 'year_level' => 4,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        foreach ([2 => 'BA 201', 4 => 'BA 401'] as $yearLevel => $code) {
            Subject::create([
                'course' => 'BSBA', 'code' => $code, 'name' => "Year {$yearLevel} Major Subject",
                'subject_type' => 'Lecture', 'classification' => 'Major',
                'year_level' => $yearLevel, 'semester' => '1st', 'units' => 3,
            ])->instructors()->attach($instructor);
        }

        // Generate the higher year first to prove that it leaves the priority
        // room period available for the lower-year section.
        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => 4,
            'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => 2,
            'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $secondYearSchedule = ClassSchedule::where('section_id', $secondYear->id)->firstOrFail();
        $fourthYearSchedule = ClassSchedule::where('section_id', $fourthYear->id)->firstOrFail();

        $this->assertSame($room->id, $secondYearSchedule->room_id);
        $this->assertSame($room->id, $fourthYearSchedule->room_id);
        $this->assertSame('08:30', substr($secondYearSchedule->start_time, 0, 5));
        $this->assertSame('16:30', substr($fourthYearSchedule->start_time, 0, 5));
    }

    public function test_lower_year_generation_does_not_require_monday_to_saturday_when_no_section_has_three_subjects(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'employment_type' => null, 'account_status' => 'active',
        ]);
        Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);
        AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section 1', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        Subject::create([
            'course' => 'BSIT', 'code' => 'IT101', 'name' => 'Only Major Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => 1,
            'number_of_sections' => 1,
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('class_schedules', 1);
    }

    public function test_only_one_eligible_lower_year_section_is_scheduled_from_monday_to_saturday(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->count(3)->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => null,
            'account_status' => 'active',
        ]);
        Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);
        $sections = collect(['1 - Alpha', '1 - Bravo', '1 - Charlie'])->map(
            fn (string $name): AcademicSection => AcademicSection::create([
                'course' => 'BSIT',
                'name' => $name,
                'year_level' => 1,
                'academic_year' => '2026-2027',
                'semester' => 'All',
            ]),
        );

        foreach (range(1, 3) as $number) {
            Subject::create([
                'course' => 'BSIT',
                'code' => "ITE {$number}",
                'name' => "Coverage Subject {$number}",
                'subject_type' => 'Lecture',
                'classification' => 'Major',
                'year_level' => 1,
                'semester' => '1st',
                'units' => 3,
            ]);
        }

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'year_level' => 1,
            'number_of_sections' => 3,
        ])->assertRedirect()->assertSessionHas('success');

        $daysForSection = fn (AcademicSection $section): array => ClassSchedule::query()
            ->where('section_id', $section->id)
            ->pluck('day')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['F - S', 'M - W', 'T - Th'], $daysForSection($sections[0]));
        $this->assertNotContains('F - S', $daysForSection($sections[1]));
        $this->assertNotContains('F - S', $daysForSection($sections[2]));
    }

    public function test_schedule_failure_note_identifies_the_specific_instructor_conflict(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSBA',
            'employment_type' => 'full_time',
            'account_status' => 'active',
            'first_name' => 'Busy',
            'middle_name' => null,
            'last_name' => 'Instructor',
            'suffix' => null,
            'teaching_unit_limit' => 60,
        ]);
        $targetSection = AcademicSection::create([
            'course' => 'BSBA',
            'name' => '2 - Southwest',
            'year_level' => 2,
            'academic_year' => '2026-2027',
            'semester' => 'All',
        ]);
        $blockingSection = AcademicSection::create([
            'course' => 'BSBA',
            'name' => '4 - North',
            'year_level' => 4,
            'academic_year' => '2026-2027',
            'semester' => 'All',
        ]);
        $room = Room::create([
            'course' => 'BSBA',
            'name' => 'BA 101',
            'room_type' => 'Lecture',
        ]);
        $blockingSubject = Subject::create([
            'course' => 'BSBA',
            'code' => 'BUSY 101',
            'name' => 'Existing Instructor Classes',
            'subject_type' => 'Lecture',
            'classification' => 'Major',
            'year_level' => 4,
            'semester' => '1st',
            'units' => 3,
        ]);
        $targetSubject = Subject::create([
            'course' => 'BSBA',
            'code' => 'BA 201',
            'name' => 'Target Subject',
            'subject_type' => 'Lecture',
            'classification' => 'Major',
            'year_level' => 2,
            'semester' => '1st',
            'units' => 3,
        ]);
        $targetSubject->instructors()->attach($instructor);

        foreach ([
            ['M - W', '07:00', '09:30'],
            ['M - W', '09:30', '12:00'],
            ['M - W', '13:00', '15:30'],
            ['M - W', '15:30', '18:00'],
            ['M - W', '16:30', '19:00'],
            ['T - Th', '07:00', '09:30'],
            ['T - Th', '09:30', '12:00'],
            ['T - Th', '13:00', '15:30'],
            ['T - Th', '15:30', '18:00'],
            ['T - Th', '16:30', '19:00'],
            ['F - S', '07:00', '09:30'],
            ['F - S', '09:30', '12:00'],
            ['F - S', '13:00', '15:30'],
            ['F - S', '15:30', '18:00'],
            ['F - S', '16:30', '19:00'],
        ] as [$day, $start, $end]) {
            ClassSchedule::create([
                'course' => 'BSBA',
                'section_id' => $blockingSection->id,
                'subject_id' => $blockingSubject->id,
                'instructor_id' => $instructor->id,
                'room_id' => $room->id,
                'academic_year' => '2026-2027',
                'semester' => '1st',
                'day' => $day,
                'start_time' => $start,
                'end_time' => $end,
            ]);
        }

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'year_level' => 2,
            'number_of_sections' => 1,
        ])->assertRedirect()
            ->assertSessionHas('error')
            ->assertSessionHas('error_note');

        $note = (string) session('error_note');
        $this->assertStringContainsString('BA 201 is a Major Lecture subject', $note);
        $this->assertStringContainsString('Busy Instructor already has 3 schedules on each remaining day pair', $note);

        $this->assertDatabaseMissing('class_schedules', [
            'section_id' => $targetSection->id,
            'subject_id' => $targetSubject->id,
        ]);
    }

    public function test_full_time_workload_over_30_units_rolls_back_the_entire_generation(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'employment_type' => 'full_time', 'account_status' => 'active',
        ]);
        Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);
        AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section 1', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        foreach (range(1, 11) as $number) {
            Subject::create([
                'course' => 'BSIT', 'code' => "IT{$number}", 'name' => "Major Subject {$number}",
                'subject_type' => 'Lecture', 'classification' => 'Major',
                'year_level' => 1, 'semester' => '1st', 'units' => 3,
            ]);
        }

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => 1,
            'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseCount('class_schedules', 0);
    }

    public function test_schedule_generation_reports_all_workload_shortages_in_one_warning(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => 'full_time',
            'account_status' => 'active',
            'teaching_unit_limit' => 3,
        ]);
        Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);
        AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - North', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        foreach (range(1, 3) as $number) {
            $subject = Subject::create([
                'course' => 'BSIT',
                'code' => "ITE {$number}",
                'name' => "Capacity Subject {$number}",
                'subject_type' => 'Lecture',
                'classification' => 'Major',
                'year_level' => 1,
                'semester' => '1st',
                'units' => 3,
            ]);
            $subject->instructors()->attach($instructor->id, ['priority' => 1]);
        }

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'year_level' => 1,
            'number_of_sections' => 1,
        ])->assertRedirect()
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'Schedule was not created because these classes do not have an instructor'))
            ->assertSessionHas('error_note', fn (string $note): bool => str_contains($note, 'Open Subject Assignment.'));

        $this->assertSame(2, substr_count((string) session('error'), '1 section, 3 hours each'));
        $this->assertDatabaseCount('class_schedules', 0);
    }

    public function test_full_time_instructor_is_not_required_to_use_the_30_unit_maximum(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSBA',
            'employment_type' => 'full_time',
            'account_status' => 'active',
            'first_name' => 'Cheska',
            'middle_name' => null,
            'last_name' => 'Jumantoc',
        ]);
        Room::create([
            'course' => 'BSBA',
            'name' => 'BA 101',
            'room_type' => 'Lecture',
        ]);
        AcademicSection::create([
            'course' => 'BSBA',
            'name' => '2 - Southwest',
            'year_level' => 2,
            'academic_year' => '2026-2027',
            'semester' => 'All',
        ]);

        foreach (range(1, 6) as $number) {
            $subject = Subject::create([
                'course' => 'BSBA',
                'code' => "BA 20{$number}",
                'name' => "Full-Time Load Subject {$number}",
                'subject_type' => 'Lecture',
                'classification' => 'Major',
                'year_level' => 2,
                'semester' => '1st',
                'units' => 3,
            ]);
            $subject->instructors()->attach($instructor);
        }

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'year_level' => 2,
            'number_of_sections' => 1,
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame([0, 30], app(ClassScheduleGenerator::class)->workloadRange($instructor));
        $this->assertDatabaseCount('class_schedules', 6);
        $this->assertSame(
            18.0,
            ClassSchedule::with('subject')->get()->sum(
                fn (ClassSchedule $schedule): float => (float) $schedule->subject->units,
            ),
        );
    }

    public function test_manual_timetable_edit_blocks_invalid_duration_but_allows_major_subjects_on_friday_and_saturday(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'employment_type' => 'full_time', 'account_status' => 'active',
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section 1', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'IT101', 'name' => 'Programming',
            'subject_type' => 'Lecture', 'classification' => 'Major',
            'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $schedule = ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'academic_year' => '2026-2027', 'semester' => '1st',
            'day' => 'M - W', 'start_time' => '08:30', 'end_time' => '10:00',
        ]);

        $this->actingAs($dean)->put(route('dean.timetable.update', $schedule), [
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'day' => 'M - W', 'start_time' => '12:00', 'end_time' => '13:00',
        ])->assertRedirect()->assertSessionHas('error');

        $this->actingAs($dean)->put(route('dean.timetable.update', $schedule), [
            'instructor_id' => $instructor->id, 'room_id' => $room->id,
            'day' => 'F - S', 'start_time' => '07:30', 'end_time' => '09:00',
        ])->assertRedirect()->assertSessionHas('success');

        $schedule->refresh();
        $this->assertSame('F - S', $schedule->day);
        $this->assertSame('07:30', substr($schedule->start_time, 0, 5));
    }

    public function test_dean_can_create_section_without_selecting_a_semester(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);

        $this->actingAs($dean)->post(route('dean.sections.store'), [
            'name' => 'Year 2-A',
            'year_level' => 2,
            'academic_year' => '2026-2027',
        ])->assertRedirect(route('dean.sections.create'));

        $this->assertDatabaseHas('academic_sections', [
            'course' => 'BSIT',
            'name' => 'Year 2-A',
            'year_level' => 2,
            'academic_year' => '2026-2027',
        ]);
    }

    public function test_subject_creation_and_instructor_assignment_are_separate_features(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructors = User::factory()->count(6)->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'account_status' => 'active',
        ]);

        $this->actingAs($dean)->post(route('dean.subjects.store'), [
            'code' => 'IT202',
            'name' => 'Advanced Programming',
            'subject_type' => 'Lecture',
            'classification' => 'Minor',
            'year_level' => 2,
            'semester' => '1st',
            'curriculum' => 'Old',
            'units' => 3,
        ])->assertRedirect(route('dean.subjects.index'));

        $subject = Subject::where('code', 'IT202')->firstOrFail();
        $this->assertCount(0, $subject->instructors);
        $this->assertSame('Old', $subject->curriculum);
        $this->assertSame('Major', $subject->classification);
        Subject::create([
            'course' => 'BSIT',
            'code' => 'IT299',
            'name' => 'Unrelated Subject',
            'subject_type' => 'Lecture',
            'classification' => 'Major',
            'year_level' => 2,
            'semester' => '1st',
            'curriculum' => 'New',
            'units' => 3,
        ]);

        // Subject creation is a modal on the index page, not a dedicated
        // page — the standalone create route just redirects there.
        $this->actingAs($dean)
            ->get(route('dean.subjects.create'))
            ->assertRedirect(route('dean.subjects.index'));

        $this->actingAs($dean)
            ->get(route('dean.subjects.index'))
            ->assertOk()
            ->assertSee('Enter the curriculum information for BSIT')
            ->assertSee('type="hidden" name="curriculum" value="New"', false)
            ->assertDontSee('id="subject_classification"', false)
            ->assertDontSee('name="instructor_department"', false)
            ->assertDontSee('name="instructor_ids[]"', false);

        $this->actingAs($dean)
            ->get(route('dean.subjects.index', ['curriculum' => 'Old']))
            ->assertOk()
            ->assertSee('Advanced Programming')
            ->assertDontSee('Unrelated Subject');

        $this->actingAs($dean)
            ->get(route('dean.subject-assignments.index'))
            ->assertOk()
            ->assertSee('Assign Instructor to a Subject')
            ->assertSee('name="semester"', false)
            ->assertSee('name="instructor_department"', false)
            ->assertSee('name="instructor_ids[]"', false)
            ->assertSeeInOrder(['Existing Subject Assignments', 'Assign Instructor to a Subject']);

        $this->actingAs($dean)
            ->get(route('dean.subject-assignments.index', ['search' => 'Advanced']))
            ->assertOk()
            ->assertSee('name="search"', false)
            ->assertSee('Advanced Programming');

        $this->actingAs($dean)
            ->get(route('dean.subject-assignments.create'))
            ->assertOk()
            ->assertSee('Assign Instructor to a Subject')
            ->assertSee('name="semester"', false)
            ->assertSee('name="subject_id"', false)
            ->assertSee('name="instructor_department"', false)
            ->assertSee('name="instructor_ids[]"', false)
            ->assertSee('type="search"', false)
            ->assertSee('Search for the primary instructor', false)
            ->assertSee('Priority 6')
            ->assertSee('value="Summer"', false)
            ->assertSee('label="Second Year"', false)
            ->assertSeeInOrder(['Semester', 'Year Level', 'Subject', 'Instructor Priorities', 'Submit Assignment']);

        $this->actingAs($dean)
            ->get(route('dean.subject-assignments.create', ['subject_id' => $subject->id]))
            ->assertOk()
            ->assertSee('Assign Instructor to a Subject')
            ->assertSee('Advanced Programming')
            ->assertSee('name="instructor_department"', false)
            ->assertSee('name="instructor_ids[]"', false)
            ->assertSee($instructors[0]->name)
            ->assertSee($instructors[1]->name)
            ->assertDontSee('Create Subject')
            ->assertDontSee('Create a New Subject');

        $this->actingAs($dean)->post(route('dean.subject-assignments.store'), [
            'semester' => '1st',
            'subject_id' => $subject->id,
            'instructor_ids' => $instructors->pluck('id')->all(),
        ])->assertRedirect(route('dean.subject-assignments.index'));

        $this->assertCount(6, $subject->refresh()->instructors);

        $this->actingAs($dean)->put(route('dean.subjects.update', $subject), [
            'code' => 'IT202',
            'name' => 'Advanced Programming Updated',
            'subject_type' => 'Lecture',
            'classification' => 'Major',
            'year_level' => 2,
            'semester' => '1st',
            'curriculum' => 'New',
            'units' => 3,
        ])->assertRedirect(route('dean.subjects.index'));

        $this->assertCount(6, $subject->refresh()->instructors);
        $this->assertSame('New', $subject->curriculum);
        $this->actingAs($dean)
            ->get(route('dean.subjects.index'))
            ->assertOk()
            ->assertViewHas('subjectsByYear', fn ($subjectsByYear): bool => $subjectsByYear->get(2)->contains('id', $subject->id))
            ->assertSeeInOrder(['First Year Subjects', 'Second Year Subjects', 'Third Year Subjects', 'Fourth Year Subjects'])
            ->assertSee('edit='.$subject->id, false)
            ->assertSee('delete-confirmation-trigger', false);
    }

    public function test_dean_subject_creation_creates_only_one_record_when_legacy_semester_flags_are_all_enabled(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA']);
        Department::where('code', 'BSBA')->firstOrFail()->update([
            'semester_first_enabled' => true,
            'semester_second_enabled' => true,
            'semester_summer_enabled' => true,
        ]);

        $this->actingAs($dean)->post(route('dean.subjects.store'), [
            'code' => 'BA 101',
            'name' => 'Business Fundamentals',
            'subject_type' => 'Lecture',
            'classification' => 'Major',
            'year_level' => 1,
            'curriculum' => 'New',
            'units' => 3,
        ])->assertRedirect(route('dean.subjects.index'));

        $subjects = Subject::query()->forDepartment('BSBA')->where('code', 'BA 101')->get();

        $this->assertCount(1, $subjects);
        $this->assertSame('1st', $subjects->sole()->semester);
    }

    public function test_subject_code_uniqueness_is_scoped_to_the_selected_curriculum(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $subjectData = [
            'code' => 'ITE 111',
            'name' => 'Introduction in Computing',
            'subject_type' => 'Laboratory',
            'classification' => 'Major',
            'year_level' => 1,
            'semester' => '1st',
            'units' => 3,
        ];

        $this->actingAs($dean)->post(route('dean.subjects.store'), [
            ...$subjectData,
            'curriculum' => 'Old',
        ])->assertRedirect(route('dean.subjects.index'));

        $this->actingAs($dean)->post(route('dean.subjects.store'), [
            ...$subjectData,
            'name' => 'Introduction in Computing Revised',
            'curriculum' => 'New',
        ])->assertRedirect(route('dean.subjects.index'));

        // A Dean-created subject belongs only to the currently active semester,
        // so one record is kept for each selected curriculum.
        $this->assertSame(2, Subject::where('course', 'BSIT')->where('code', 'ITE 111')->count());

        $this->actingAs($dean)->post(route('dean.subjects.store'), [
            ...$subjectData,
            'name' => 'Duplicate New Curriculum Subject',
            'curriculum' => 'New',
        ])->assertSessionHasErrors([
            'code' => 'ITE 111 already exists in New Curriculum for BSIT. Choose another code or select the other curriculum.',
        ]);

        $this->assertSame(
            1,
            Subject::where('course', 'BSIT')->where('curriculum', 'New')->where('code', 'ITE 111')->count(),
        );
    }

    public function test_subject_assignment_excludes_instructors_who_reached_their_unit_limit(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $maxedInstructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => 'full_time',
            'account_status' => 'active',
            'first_name' => 'Maxed',
            'last_name' => 'Instructor',
        ]);
        $availableInstructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => 'full_time',
            'account_status' => 'active',
            'first_name' => 'Available',
            'last_name' => 'Instructor',
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT',
            'name' => '1 - North',
            'year_level' => 1,
            'academic_year' => '2026-2027',
            'semester' => 'All',
        ]);
        $loadSubject = Subject::create([
            'course' => 'BSIT',
            'code' => 'LOAD 101',
            'name' => 'Existing Teaching Load',
            'subject_type' => 'Lecture',
            'classification' => 'Major',
            'year_level' => 1,
            'semester' => '1st',
            'units' => 3,
        ]);
        $targetSubject = Subject::create([
            'course' => 'BSIT',
            'code' => 'NEW 101',
            'name' => 'New Subject Assignment',
            'subject_type' => 'Lecture',
            'classification' => 'Major',
            'year_level' => 1,
            'semester' => '1st',
            'units' => 3,
        ]);

        foreach (range(1, 10) as $entry) {
            ClassSchedule::create([
                'course' => 'BSIT',
                'section_id' => $section->id,
                'subject_id' => $loadSubject->id,
                'instructor_id' => $maxedInstructor->id,
                'room_id' => null,
                'academic_year' => '2026-2027',
                'semester' => '1st',
                'day' => 'M - W',
                'start_time' => '07:00',
                'end_time' => '09:30',
            ]);
        }

        foreach (range(1, 9) as $entry) {
            ClassSchedule::create([
                'course' => 'BSIT',
                'section_id' => $section->id,
                'subject_id' => $loadSubject->id,
                'instructor_id' => $availableInstructor->id,
                'room_id' => null,
                'academic_year' => '2026-2027',
                'semester' => '1st',
                'day' => 'T - Th',
                'start_time' => '09:30',
                'end_time' => '12:00',
            ]);
        }

        $this->actingAs($dean)
            ->get(route('dean.subject-assignments.create', ['subject_id' => $targetSubject->id]))
            ->assertOk()
            ->assertSee('Instructors who cannot accept the selected subject without exceeding their workload-hour limit are hidden.')
            ->assertSee('"'.$maxedInstructor->id.'":{"1st":30}', false)
            ->assertSee('"'.$availableInstructor->id.'":{"1st":27}', false);

        $this->actingAs($dean)->post(route('dean.subject-assignments.store'), [
            'semester' => '1st',
            'subject_id' => $targetSubject->id,
            'instructor_ids' => [$maxedInstructor->id],
        ])->assertSessionHasErrors('instructor_ids');

        $this->assertDatabaseMissing('subject_instructor', [
            'subject_id' => $targetSubject->id,
            'instructor_id' => $maxedInstructor->id,
        ]);

        $this->actingAs($dean)->post(route('dean.subject-assignments.store'), [
            'semester' => '1st',
            'subject_id' => $targetSubject->id,
            'instructor_ids' => [$availableInstructor->id],
        ])->assertRedirect(route('dean.subject-assignments.index'));

        $this->assertDatabaseHas('subject_instructor', [
            'subject_id' => $targetSubject->id,
            'instructor_id' => $availableInstructor->id,
        ]);
    }

    public function test_dean_can_request_an_instructor_from_another_department(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $bsbaDean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA', 'account_status' => 'active']);
        $outsideInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active',
        ]);
        $outsideInstructorTwo = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active',
        ]);
        $outsideInstructorThree = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active',
        ]);
        $outsideInstructorFour = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active',
        ]);
        $outsideInstructorFive = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active',
        ]);
        $outsideInstructorSix = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 250', 'name' => 'IT Department Only',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 2,
            'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);
        Notification::fake();

        $this->actingAs($dean)
            ->get(route('dean.subject-assignments.create', ['subject_id' => $subject->id]))
            ->assertOk()
            ->assertSee('name="instructor_department"', false)
            ->assertSee('value="BSBA"', false);

        $this->actingAs($dean)->post(route('dean.subject-assignments.store'), [
            'semester' => '1st',
            'year_level' => 2,
            'subject_id' => $subject->id,
            'instructor_department' => 'BSBA',
        ])->assertRedirect(route('dean.subject-assignments.index'));

        $this->assertDatabaseMissing('subject_instructor', [
            'subject_id' => $subject->id,
            'instructor_id' => $outsideInstructor->id,
        ]);
        $this->assertDatabaseHas('cross_department_instructor_requests', [
            'subject_id' => $subject->id,
            'requesting_department' => 'BSIT',
            'requested_department' => 'BSBA',
            'status' => 'pending',
        ]);
        Notification::assertSentTo($bsbaDean, \App\Notifications\CrossDepartmentInstructorRequestNotification::class);

        $instructorRequest = CrossDepartmentInstructorRequest::firstOrFail();
        $this->actingAs($bsbaDean)
            ->get(route('dean.instructor-requests.index'))
            ->assertOk()
            ->assertSee('ITE 250')
            ->assertSee('BSIT')
            ->assertSee('Priority 1')
            ->assertSee('Priority 6');

        $this->actingAs($bsbaDean)->post(route('dean.instructor-requests.fulfill', $instructorRequest), [
            'instructor_ids' => [
                $outsideInstructor->id,
                $outsideInstructorTwo->id,
                $outsideInstructorThree->id,
                $outsideInstructorFour->id,
                $outsideInstructorFive->id,
                $outsideInstructorSix->id,
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('cross_department_instructor_requests', [
            'id' => $instructorRequest->id,
            'status' => 'fulfilled',
            'assigned_instructor_id' => $outsideInstructor->id,
        ]);
        $this->assertDatabaseHas('subject_instructor', [
            'subject_id' => $subject->id,
            'instructor_id' => $outsideInstructor->id,
            'priority' => 1,
        ]);
        $this->assertDatabaseHas('subject_instructor', [
            'subject_id' => $subject->id,
            'instructor_id' => $outsideInstructorTwo->id,
            'priority' => 2,
        ]);
        $this->assertDatabaseHas('subject_instructor', [
            'subject_id' => $subject->id,
            'instructor_id' => $outsideInstructorSix->id,
            'priority' => 6,
        ]);
        $this->assertDatabaseHas('cross_department_instructor_request_assignments', [
            'instructor_request_id' => $instructorRequest->id,
            'instructor_id' => $outsideInstructor->id,
            'priority' => 1,
        ]);
        $this->assertDatabaseHas('cross_department_instructor_request_assignments', [
            'instructor_request_id' => $instructorRequest->id,
            'instructor_id' => $outsideInstructorTwo->id,
            'priority' => 2,
        ]);
        $this->assertDatabaseHas('cross_department_instructor_request_assignments', [
            'instructor_request_id' => $instructorRequest->id,
            'instructor_id' => $outsideInstructorSix->id,
            'priority' => 6,
        ]);
        Notification::assertSentTo($dean, \App\Notifications\CrossDepartmentInstructorAssignedNotification::class);
    }

    public function test_dean_can_clear_only_its_fulfilled_outgoing_instructor_request_history(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT', 'account_status' => 'active']);
        $fulfilledSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 310', 'name' => 'Completed External Request',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 3,
            'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);
        $pendingSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 311', 'name' => 'Pending External Request',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 3,
            'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);
        $fulfilledRequest = CrossDepartmentInstructorRequest::create([
            'subject_id' => $fulfilledSubject->id,
            'requesting_department' => 'BSIT',
            'requested_department' => 'BSBA',
            'requested_by' => $dean->id,
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
        ]);
        $pendingRequest = CrossDepartmentInstructorRequest::create([
            'subject_id' => $pendingSubject->id,
            'requesting_department' => 'BSIT',
            'requested_department' => 'BSBA',
            'requested_by' => $dean->id,
            'status' => 'pending',
        ]);

        $this->actingAs($dean)
            ->get(route('dean.instructor-requests.index'))
            ->assertOk()
            ->assertSee('Active Requests You Sent')
            ->assertSee('Request History')
            ->assertSee('ITE 310')
            ->assertSee('ITE 311')
            ->assertSee('Clear History');

        $this->actingAs($dean)
            ->post(route('dean.instructor-requests.clear-history'))
            ->assertRedirect();

        $this->assertNotNull($fulfilledRequest->fresh()->archived_at);
        $this->assertNull($pendingRequest->fresh()->archived_at);

        $this->actingAs($dean)
            ->get(route('dean.instructor-requests.index'))
            ->assertOk()
            ->assertDontSee('ITE 310')
            ->assertSee('ITE 311')
            ->assertDontSee('Clear History');
    }

    public function test_dean_moves_fulfilled_incoming_instructor_requests_to_request_history(): void
    {
        $itDean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT', 'account_status' => 'active']);
        $bsbaDean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA', 'account_status' => 'active']);
        $fulfilledSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 320', 'name' => 'Incoming Fulfilled Request',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 3,
            'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);
        $pendingSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 321', 'name' => 'Incoming Pending Request',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 3,
            'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);
        $fulfilledRequest = CrossDepartmentInstructorRequest::create([
            'subject_id' => $fulfilledSubject->id,
            'requesting_department' => 'BSIT',
            'requested_department' => 'BSBA',
            'requested_by' => $itDean->id,
            'status' => 'fulfilled',
            'fulfilled_by' => $bsbaDean->id,
            'fulfilled_at' => now(),
        ]);
        $pendingRequest = CrossDepartmentInstructorRequest::create([
            'subject_id' => $pendingSubject->id,
            'requesting_department' => 'BSIT',
            'requested_department' => 'BSBA',
            'requested_by' => $itDean->id,
            'status' => 'pending',
        ]);

        $this->actingAs($bsbaDean)
            ->get(route('dean.instructor-requests.index'))
            ->assertOk()
            ->assertViewHas('incomingActive', fn ($requests) => $requests->contains('id', $pendingRequest->id)
                && ! $requests->contains('id', $fulfilledRequest->id))
            ->assertViewHas('requestHistory', fn ($requests) => $requests->contains('id', $fulfilledRequest->id))
            ->assertSee('Incoming')
            ->assertSee('ITE 320')
            ->assertSee('ITE 321');

        $this->actingAs($bsbaDean)
            ->post(route('dean.instructor-requests.clear-history'))
            ->assertRedirect();

        $this->assertNotNull($fulfilledRequest->fresh()->requested_department_archived_at);
        $this->assertNull($fulfilledRequest->fresh()->archived_at);

        $this->actingAs($itDean)
            ->get(route('dean.instructor-requests.index'))
            ->assertOk()
            ->assertSee('ITE 320');
    }

    public function test_pending_request_is_delivered_when_the_requested_department_dean_is_created(): void
    {
        $itDean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT', 'account_status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 260', 'name' => 'Cross-Department Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 2,
            'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);
        Notification::fake();

        $this->actingAs($itDean)->post(route('dean.subject-assignments.store'), [
            'semester' => '1st',
            'year_level' => 2,
            'subject_id' => $subject->id,
            'instructor_department' => 'BSBA',
        ])->assertRedirect()
            ->assertSessionHas('success', 'A BSBA instructor was requested for ITE 260, but no active BSBA Dean account exists yet. The request is queued and will be delivered when that Dean account is created or activated.');

        Notification::assertNothingSent();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'BSBA',
            'last_name' => 'Dean',
            'email' => 'bsba.dean@example.test',
            'role' => 'dean',
            'course' => 'BSBA',
            'account_status' => 'active',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect(route('admin.users.index'));

        $bsbaDean = User::where('email', 'bsba.dean@example.test')->firstOrFail();
        Notification::assertSentTo($bsbaDean, \App\Notifications\CrossDepartmentInstructorRequestNotification::class);
    }

    public function test_scheduler_moves_major_laboratory_overflow_to_the_next_instructor_priority_by_workload_hours(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $priorityOne = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
            'employment_type' => 'full_time', 'teaching_unit_limit' => 6,
            'first_name' => 'Kurt', 'last_name' => 'Alegre',
        ]);
        $priorityTwo = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
            'employment_type' => 'full_time', 'teaching_unit_limit' => 30,
            'first_name' => 'Juniel', 'last_name' => 'Marfa',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 211', 'name' => 'Computer Programming 2',
            'subject_type' => 'Laboratory', 'classification' => 'Major', 'year_level' => 2,
            'semester' => '1st', 'units' => 3,
        ]);
        $sections = collect(['2 - North', '2 - East', '2 - West'])->map(fn (string $name) => AcademicSection::create([
            'course' => 'BSIT', 'name' => $name, 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]));
        $room = Room::create([
            'course' => 'BSIT', 'name' => 'Laboratory 1', 'room_type' => 'Laboratory', 'capacity' => 40,
        ]);

        $this->actingAs($dean)->post(route('dean.subject-assignments.store'), [
            'semester' => '1st',
            'year_level' => 2,
            'subject_id' => $subject->id,
            'instructor_department' => 'BSIT',
            'instructor_ids' => [$priorityOne->id, $priorityTwo->id, null, null],
        ])->assertRedirect();

        $this->assertDatabaseHas('subject_instructor', [
            'subject_id' => $subject->id, 'instructor_id' => $priorityOne->id, 'priority' => 1,
        ]);
        $this->assertDatabaseHas('subject_instructor', [
            'subject_id' => $subject->id, 'instructor_id' => $priorityTwo->id, 'priority' => 2,
        ]);

        app(ClassScheduleGenerator::class)->generate(
            'BSIT',
            $sections,
            collect([$subject->fresh('instructors')]),
            collect([$room]),
            collect([$priorityOne, $priorityTwo]),
            ['academic_year' => '2026-2027', 'semester' => '1st'],
        );

        // Priority 1 has a six-hour limit. A Major Laboratory consumes five
        // workload hours, so its second section must move to Priority 2.
        $this->assertSame(1, ClassSchedule::where('subject_id', $subject->id)->where('instructor_id', $priorityOne->id)->count());
        $this->assertSame(2, ClassSchedule::where('subject_id', $subject->id)->where('instructor_id', $priorityTwo->id)->count());
    }

    public function test_non_dean_cannot_access_dean_portal(): void
    {
        $student = User::factory()->create(['role' => 'student', 'course' => 'BSIT']);

        $this->actingAs($student)->get(route('dean.dashboard'))->assertForbidden();
    }

    public function test_dean_can_approve_pending_instructor_in_their_department(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'account_status' => 'pending',
        ]);

        $this->actingAs($dean)
            ->patch(route('dean.instructors.approve', $instructor))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $instructor->id,
            'account_status' => 'active',
        ]);
    }

    public function test_pending_instructors_are_not_shown_in_the_active_instructor_list(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $pending = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'pending']);
        $active = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);

        $this->actingAs($dean)
            ->get(route('dean.instructors.index'))
            ->assertOk()
            ->assertDontSee('Pending Instructor Registrations')
            ->assertSee($active->name)
            ->assertDontSee($pending->name);
    }

    public function test_laboratory_subjects_are_tba_when_no_laboratory_room_is_available(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active', 'employment_type' => 'full_time',
        ]);
        $section = AcademicSection::create([
            'course' => 'BSBA', 'name' => '2 - East', 'year_level' => 2, 'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $room = Room::create(['course' => 'BSBA', 'name' => 'BA 101', 'room_type' => 'Lecture']);
        $subject = Subject::create([
            'course' => 'BSBA', 'code' => 'BA LAB 1', 'name' => 'Business Laboratory',
            'subject_type' => 'Laboratory', 'classification' => 'Major', 'year_level' => 2, 'semester' => '1st', 'units' => 3,
        ]);
        $subject->instructors()->attach($instructor, ['priority' => 1]);

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => '2', 'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('class_schedules', [
            'section_id' => $section->id, 'subject_id' => $subject->id, 'room_id' => null,
        ]);
    }

    public function test_subjects_use_rooms_matching_their_laboratory_or_lecture_type(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSHM']);
        $instructors = User::factory()->count(2)->create([
            'role' => 'instructor', 'course' => 'BSHM', 'account_status' => 'active', 'employment_type' => 'full_time',
        ]);
        AcademicSection::create([
            'course' => 'BSHM', 'name' => '2 - North', 'year_level' => 2, 'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $kitchen = Room::create(['course' => 'BSHM', 'name' => 'Kitchen Lab 1', 'room_type' => 'Kitchen Laboratory']);
        $lecture = Room::create(['course' => 'BSHM', 'name' => 'HM 101', 'room_type' => 'Lecture']);
        $cooking = Subject::create([
            'course' => 'BSHM', 'code' => 'HM 201', 'name' => 'Culinary Food Preparation',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 2, 'semester' => '1st', 'units' => 3,
        ]);
        $management = Subject::create([
            'course' => 'BSHM', 'code' => 'HM 202', 'name' => 'Hospitality Management',
            'subject_type' => 'Laboratory', 'classification' => 'Major', 'year_level' => 2, 'semester' => '1st', 'units' => 3,
        ]);
        $cooking->instructors()->attach($instructors[0], ['priority' => 1]);
        $management->instructors()->attach($instructors[1], ['priority' => 1]);

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => '2', 'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame($lecture->id, ClassSchedule::where('subject_id', $cooking->id)->value('room_id'));
        $this->assertSame($kitchen->id, ClassSchedule::where('subject_id', $management->id)->value('room_id'));
    }

    public function test_instructor_cannot_receive_more_than_three_schedules_on_one_day_pair(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSBA']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSBA', 'account_status' => 'active', 'employment_type' => 'full_time',
        ]);
        $targetSection = AcademicSection::create([
            'course' => 'BSBA', 'name' => '2 - West', 'year_level' => 2, 'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $blockingSection = AcademicSection::create([
            'course' => 'BSBA', 'name' => '4 - East', 'year_level' => 4, 'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $room = Room::create(['course' => 'BSBA', 'name' => 'BA 101', 'room_type' => 'Lecture']);
        $blockingSubject = Subject::create([
            'course' => 'BSBA', 'code' => 'BUSY 401', 'name' => 'Existing Teaching Load',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 4, 'semester' => '1st', 'units' => 3,
        ]);
        $targetSubject = Subject::create([
            'course' => 'BSBA', 'code' => 'BA 201', 'name' => 'Target Management Subject',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 2, 'semester' => '1st', 'units' => 3,
        ]);
        $targetSubject->instructors()->attach($instructor, ['priority' => 1]);

        foreach (['M - W', 'T - Th', 'F - S'] as $day) {
            foreach ([['07:00', '09:30'], ['13:00', '15:30'], ['16:30', '19:00']] as [$start, $end]) {
                ClassSchedule::create([
                    'course' => 'BSBA', 'section_id' => $blockingSection->id, 'subject_id' => $blockingSubject->id,
                    'instructor_id' => $instructor->id, 'room_id' => null,
                    'academic_year' => '2026-2027', 'semester' => '1st', 'day' => $day,
                    'start_time' => $start, 'end_time' => $end,
                ]);
            }
        }

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => '2', 'number_of_sections' => 1,
        ])->assertRedirect()
            ->assertSessionHas('error')
            ->assertSessionHas('error_note', fn (string $note): bool => str_contains($note, '3 schedules on each remaining day pair'));

        $this->assertDatabaseMissing('class_schedules', ['section_id' => $targetSection->id, 'subject_id' => $targetSubject->id]);
        $this->assertSame(9, ClassSchedule::where('subject_id', $blockingSubject->id)->count());
    }

    public function test_dean_cannot_approve_instructor_from_another_department(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSBA',
            'account_status' => 'pending',
        ]);

        $this->actingAs($dean)
            ->patch(route('dean.instructors.approve', $instructor))
            ->assertNotFound();
    }

    public function test_dean_can_remove_all_subject_assignments_only_from_their_department(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'account_status' => 'active',
        ]);
        $bsitSubject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 111', 'name' => 'Introduction in Computing',
            'subject_type' => 'Laboratory', 'classification' => 'Major', 'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $bsbaSubject = Subject::create([
            'course' => 'BSBA', 'code' => 'BA 111', 'name' => 'Business Fundamentals',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 1, 'semester' => '1st', 'units' => 3,
        ]);
        $bsitSubject->instructors()->attach($instructor, ['priority' => 1]);
        $bsbaSubject->instructors()->attach($instructor, ['priority' => 1]);

        $this->actingAs($dean)
            ->delete(route('dean.subject-assignments.destroy-all'))
            ->assertRedirect(route('dean.subject-assignments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('subject_instructor', ['subject_id' => $bsitSubject->id]);
        $this->assertDatabaseHas('subject_instructor', ['subject_id' => $bsbaSubject->id]);
        $this->assertDatabaseHas('subjects', ['id' => $bsitSubject->id]);
    }

    public function test_subject_assignment_preserves_the_active_list_filters_after_submission(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active', 'employment_type' => 'full_time',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 211', 'name' => 'Computer Programming 2',
            'subject_type' => 'Laboratory', 'classification' => 'Major', 'year_level' => 2,
            'semester' => '2nd', 'curriculum' => 'New', 'units' => 3,
        ]);
        Subject::create([
            'course' => 'BSIT', 'code' => 'ITE 211', 'name' => 'Legacy Computer Programming 2',
            'subject_type' => 'Laboratory', 'classification' => 'Major', 'year_level' => 2,
            'semester' => '2nd', 'curriculum' => 'Old', 'units' => 3,
        ]);

        $filters = [
            'year_level' => 2,
            'semester' => '2nd',
            'curriculum' => 'New',
            'assignment_status' => 'unassigned',
        ];

        $this->actingAs($dean)
            ->get(route('dean.subject-assignments.index', $filters))
            ->assertOk()
            ->assertSee('Curriculum')
            ->assertSee('New Curriculum')
            ->assertViewHas('subjects', fn ($subjects): bool => $subjects->count() === 1 && $subjects->first()->is($subject));

        $this->actingAs($dean)->post(route('dean.subject-assignments.store'), [
            'semester' => '2nd',
            'year_level' => 2,
            'subject_id' => $subject->id,
            'instructor_department' => 'BSIT',
            'instructor_ids' => [$instructor->id],
            'return_year_level' => $filters['year_level'],
            'return_semester' => $filters['semester'],
            'return_curriculum' => $filters['curriculum'],
            'return_assignment_status' => $filters['assignment_status'],
        ])->assertRedirect(route('dean.subject-assignments.index', $filters));
    }
}
