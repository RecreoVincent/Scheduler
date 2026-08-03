<?php

namespace Tests\Feature;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassScheduleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeanPortalTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSeeInOrder(['Schedule Archive', 'Deleted on', 'Academic Year 2026-2027', '1st Semester', 'Section 1', 'Restore Schedule', 'Delete Schedule', 'IT201', 'Choose'])
            ->assertSee('Delete Schedule Permanently?');

        $this->actingAs($dean)
            ->patch(route('dean.archive.restore', $schedule->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted('class_schedules', ['id' => $schedule->id]);
        $this->assertSame(1, ClassSchedule::whereKey($schedule->id)->count());
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
            ->assertSee('name="semester"', false)
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
            ->assertSee('grid-template-columns:repeat(5,minmax(0,1fr))', false)
            ->assertViewHas('analytics', fn (array $analytics): bool => $analytics['students']['Year 2'] === 1);
        $this->actingAs($dean)->get(route('dean.sections.edit', $otherSection))->assertNotFound();
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
            ->assertSee('Which class entry do you want to edit?', false)
            ->assertSee('Delete Section Schedule?');

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
            ->assertSee('Delete All Schedules');

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

    public function test_dean_can_generate_schedules_for_all_available_year_levels(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->count(2)->create([
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
            'year_level' => 'all',
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
    }

    public function test_generator_balances_shared_instructors_before_minor_slots_are_exhausted(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructors = User::factory()->count(13)->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => null,
            'account_status' => 'active',
        ]);
        // This instructor is Priority 1 for one shared subject and the backup
        // for another, so the configured capacity must cover both allocations.
        $instructors[10]->update(['teaching_unit_limit' => 30]);

        Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);
        Room::create(['course' => 'BSIT', 'name' => 'Lab 2', 'room_type' => 'Laboratory']);
        Room::create(['course' => 'BSIT', 'name' => 'Lab 3', 'room_type' => 'Laboratory']);

        foreach (range(1, 8) as $sectionNumber) {
            AcademicSection::create([
                'course' => 'BSIT',
                'name' => "1 - Section {$sectionNumber}",
                'year_level' => 1,
                'academic_year' => '2026-2027',
                'semester' => 'All',
            ]);
        }

        $subjects = collect([
            ['ITE 111', 'First Major', 'Major', 'Laboratory'],
            ['ITE 112', 'Second Major', 'Major', 'Laboratory'],
            ['GE 1', 'First Filler Minor', 'Minor', 'Lecture'],
            ['GE 2', 'Second Filler Minor', 'Minor', 'Lecture'],
            ['GE 3', 'Third Filler Minor', 'Minor', 'Lecture'],
            ['GEFIL 1', 'Shared Minor', 'Minor', 'Lecture'],
            ['NSTP 1', 'Target Minor', 'Minor', 'Lecture'],
        ])->map(fn (array $details) => Subject::create([
            'course' => 'BSIT',
            'code' => $details[0],
            'name' => $details[1],
            'classification' => $details[2],
            'subject_type' => $details[3],
            'year_level' => 1,
            'semester' => '1st',
            'units' => 3,
        ]));

        $subjects[0]->instructors()->attach([$instructors[0]->id, $instructors[1]->id]);
        $subjects[1]->instructors()->attach([$instructors[2]->id, $instructors[3]->id]);
        $subjects[2]->instructors()->attach([$instructors[4]->id, $instructors[5]->id]);
        $subjects[3]->instructors()->attach([$instructors[6]->id, $instructors[7]->id]);
        $subjects[4]->instructors()->attach([$instructors[8]->id, $instructors[9]->id]);
        $subjects[5]->instructors()->attach([
            $instructors[11]->id => ['priority' => 1],
            $instructors[10]->id => ['priority' => 2],
        ]);
        $subjects[6]->instructors()->attach([$instructors[12]->id, $instructors[10]->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'year_level' => '1',
            'number_of_sections' => 8,
        ]);
        $generationQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertLessThan(
            250,
            $generationQueryCount,
            "Schedule generation executed {$generationQueryCount} queries; conflict checks may have regressed to per-slot database queries.",
        );

        $nstpSchedules = ClassSchedule::where('subject_id', $subjects[6]->id)->get();
        $this->assertCount(8, $nstpSchedules);
        $this->assertSame(2, $nstpSchedules->pluck('instructor_id')->unique()->count());
    }

    public function test_bsit_major_schedules_use_tba_after_laboratory_slots_are_full(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->count(2)->create([
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

        $this->assertSame(9, ClassSchedule::whereNotNull('room_id')->count());
        $this->assertSame(1, ClassSchedule::whereNull('room_id')->count());
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
            'outside_work_end_time' => '15:30',
            'account_status' => 'active',
        ]);
        $flexibleInstructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => 'flexible_part_time',
            'account_status' => 'active',
        ]);
        $lectureRoom = Room::create(['course' => 'BSIT', 'name' => 'Room 101', 'room_type' => 'Lecture']);
        Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);
        AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section 1', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        foreach ([['IT101', 'Programming 1'], ['IT102', 'Programming 2']] as [$code, $name]) {
            $subject = Subject::create([
                'course' => 'BSIT', 'code' => $code, 'name' => $name,
                'subject_type' => 'Lecture', 'classification' => 'Major',
                'year_level' => 1, 'semester' => '1st', 'units' => 3,
            ]);
            $subject->instructors()->attach($industryInstructor);
        }

        foreach (range(1, 5) as $number) {
            $minorSubject = Subject::create([
                'course' => 'BSIT', 'code' => "GE10{$number}", 'name' => "Minor Laboratory {$number}",
                'subject_type' => 'Laboratory', 'classification' => 'Minor',
                'year_level' => 1, 'semester' => '1st', 'units' => 3,
            ]);
            $minorSubject->instructors()->attach($flexibleInstructor);
        }

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => 1,
            'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $schedules = ClassSchedule::with(['subject', 'room'])->get();
        $this->assertCount(7, $schedules);
        $this->assertEqualsCanonicalizing(['M - W', 'T - Th', 'F - S'], $schedules->pluck('day')->unique()->all());
        $this->assertSame([0, 15], app(ClassScheduleGenerator::class)->workloadRange($industryInstructor));
        $this->assertSame([0, 15], app(ClassScheduleGenerator::class)->workloadRange($flexibleInstructor));

        foreach ($schedules as $schedule) {
            $start = substr($schedule->start_time, 0, 5);
            $end = substr($schedule->end_time, 0, 5);
            $this->assertGreaterThanOrEqual('07:30', $start);
            $this->assertLessThanOrEqual('19:30', $end);
            $this->assertFalse($start < '13:00' && $end > '12:00');

            $durationMinutes = (int) ((strtotime($end) - strtotime($start)) / 60);
            $expectedDuration = $schedule->subject->classification === 'Minor' ? 90 : 150;
            $this->assertSame($expectedDuration, $durationMinutes);

            if ($schedule->subject->classification === 'Major') {
                $this->assertSame('Laboratory', $schedule->room->room_type);
                $this->assertContains($schedule->day, ['M - W', 'T - Th', 'F - S']);
                $this->assertSame($industryInstructor->id, $schedule->instructor_id);
                $this->assertGreaterThanOrEqual('15:30', $start);
                $this->assertNotSame($lectureRoom->id, $schedule->room_id);
            } else {
                $this->assertContains($schedule->day, ['M - W', 'T - Th', 'F - S']);
                $this->assertSame($flexibleInstructor->id, $schedule->instructor_id);
                $this->assertNull($schedule->room_id);
            }
        }

        $industryUnits = $schedules->where('instructor_id', $industryInstructor->id)->sum(fn (ClassSchedule $schedule): float => (float) $schedule->subject->units);
        $this->assertLessThanOrEqual(15, $industryUnits);
    }

    public function test_bsit_minor_subject_is_generated_with_a_tba_room(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'employment_type' => null, 'account_status' => 'active',
        ]);
        AcademicSection::create([
            'course' => 'BSIT', 'name' => 'Section 1', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'GE 3', 'name' => 'Mathematics in the Modern World',
            'subject_type' => 'Lecture', 'classification' => 'Minor',
            'year_level' => 2, 'semester' => '1st', 'units' => 3,
        ]);
        $subject->instructors()->attach($instructor);

        $this->actingAs($dean)->post(route('dean.schedules.store'), [
            'academic_year' => '2026-2027', 'semester' => '1st', 'year_level' => 2,
            'number_of_sections' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('class_schedules', [
            'subject_id' => $subject->id,
            'room_id' => null,
        ]);
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
        $this->assertSame(2, (int) $dayCounts['M - W']);
        $this->assertSame(1, (int) $dayCounts['T - Th']);
        $this->assertSame(1, (int) $dayCounts['F - S']);
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
        $this->assertSame('07:30', substr($secondYearSchedule->start_time, 0, 5));
        $this->assertSame('17:00', substr($fourthYearSchedule->start_time, 0, 5));
    }

    public function test_first_year_generation_rolls_back_when_monday_to_saturday_cannot_be_covered(): void
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
            ->assertSessionHas('error')
            ->assertSessionHas('error_note', fn (string $note): bool => str_contains($note, 'M–W, T–Th, and F–S'));

        $this->assertDatabaseCount('class_schedules', 0);
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
            ['M - W', '07:30', '10:00'],
            ['M - W', '09:30', '12:00'],
            ['M - W', '13:00', '15:30'],
            ['M - W', '15:30', '18:00'],
            ['M - W', '17:00', '19:30'],
            ['T - Th', '07:30', '10:00'],
            ['T - Th', '09:30', '12:00'],
            ['T - Th', '13:00', '15:30'],
            ['T - Th', '15:30', '18:00'],
            ['T - Th', '17:00', '19:30'],
            ['F - S', '07:30', '10:00'],
            ['F - S', '09:30', '12:00'],
            ['F - S', '13:00', '15:30'],
            ['F - S', '15:30', '18:00'],
            ['F - S', '17:00', '19:30'],
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

    public function test_manual_timetable_edit_blocks_lunch_but_allows_major_subjects_on_friday_and_saturday(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'employment_type' => 'full_time', 'account_status' => 'active',
        ]);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Lab 1', 'room_type' => 'Laboratory']);
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
            'day' => 'F - S', 'start_time' => '07:30', 'end_time' => '10:00',
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

    public function test_subject_creation_and_instructor_assignment_use_separate_pages(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        $instructors = User::factory()->count(2)->create([
            'role' => 'instructor',
            'course' => 'BSBA',
            'account_status' => 'active',
        ]);

        $this->actingAs($dean)->post(route('dean.subjects.store'), [
            'code' => 'IT202',
            'name' => 'Advanced Programming',
            'subject_type' => 'Lecture',
            'year_level' => 2,
            'semester' => '1st',
            'curriculum' => 'Old',
            'units' => 3,
        ])->assertRedirect(route('dean.subjects.create'));

        $subject = Subject::where('code', 'IT202')->firstOrFail();
        $this->assertCount(0, $subject->instructors);
        $this->assertSame('Old', $subject->curriculum);
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

        $this->actingAs($dean)
            ->get(route('dean.subjects.create'))
            ->assertOk()
            ->assertSee('Enter the curriculum information for BSIT')
            ->assertSee('name="curriculum"', false)
            ->assertSee('New Curriculum')
            ->assertSee('Old Curriculum')
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
            ->assertSeeInOrder(['Assign Instructor to a Subject', 'Existing Subject Assignments']);

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
            ->assertSee('value="Summer"', false)
            ->assertSee('label="Second Year"', false)
            ->assertSeeInOrder(['Semester', 'Subject', 'Department / Program', 'Instructor', 'Submit Assignment']);

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
            'instructor_department' => 'BSBA',
            'instructor_ids' => $instructors->pluck('id')->all(),
        ])->assertRedirect(route('dean.subject-assignments.index'));

        $this->assertCount(2, $subject->refresh()->instructors);

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

        $this->assertCount(2, $subject->refresh()->instructors);
        $this->assertSame('New', $subject->curriculum);
        $this->actingAs($dean)
            ->get(route('dean.subjects.index'))
            ->assertOk()
            ->assertViewHas('subjectsByYear', fn ($subjectsByYear): bool => $subjectsByYear->get(2)->contains('id', $subject->id))
            ->assertSeeInOrder(['First Year Subjects', 'Second Year Subjects', 'Third Year Subjects', 'Fourth Year Subjects'])
            ->assertSee('Which subject do you want to edit?', false)
            ->assertSee('Which subject do you want to delete?', false);
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
        ])->assertRedirect(route('dean.subjects.create'));

        $this->actingAs($dean)->post(route('dean.subjects.store'), [
            ...$subjectData,
            'name' => 'Introduction in Computing Revised',
            'curriculum' => 'New',
        ])->assertRedirect(route('dean.subjects.create'));

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
            'course' => 'BSBA',
            'employment_type' => 'full_time',
            'account_status' => 'active',
            'first_name' => 'Maxed',
            'last_name' => 'Instructor',
        ]);
        $availableInstructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSBA',
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
            ->assertSee('Instructors who cannot accept the selected subject without exceeding their unit limit are hidden.')
            ->assertSee('"'.$maxedInstructor->id.'":{"1st":30}', false)
            ->assertSee('"'.$availableInstructor->id.'":{"1st":27}', false);

        $this->actingAs($dean)->post(route('dean.subject-assignments.store'), [
            'semester' => '1st',
            'subject_id' => $targetSubject->id,
            'instructor_department' => 'BSBA',
            'instructor_ids' => [$maxedInstructor->id],
        ])->assertSessionHasErrors('instructor_ids');

        $this->assertDatabaseMissing('subject_instructor', [
            'subject_id' => $targetSubject->id,
            'instructor_id' => $maxedInstructor->id,
        ]);

        $this->actingAs($dean)->post(route('dean.subject-assignments.store'), [
            'semester' => '1st',
            'subject_id' => $targetSubject->id,
            'instructor_department' => 'BSBA',
            'instructor_ids' => [$availableInstructor->id],
        ])->assertRedirect(route('dean.subject-assignments.index'));

        $this->assertDatabaseHas('subject_instructor', [
            'subject_id' => $targetSubject->id,
            'instructor_id' => $availableInstructor->id,
        ]);
    }

    public function test_scheduler_assigns_subject_sections_using_the_deans_instructor_priority_order(): void
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
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 2,
            'semester' => '1st', 'units' => 3,
        ]);
        $sections = collect(['2 - North', '2 - East', '2 - West'])->map(fn (string $name) => AcademicSection::create([
            'course' => 'BSIT', 'name' => $name, 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]));
        $room = Room::create([
            'course' => 'BSIT', 'name' => 'Lecture 1', 'room_type' => 'Lecture', 'capacity' => 40,
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

        $this->assertSame(2, ClassSchedule::where('subject_id', $subject->id)->where('instructor_id', $priorityOne->id)->count());
        $this->assertSame(1, ClassSchedule::where('subject_id', $subject->id)->where('instructor_id', $priorityTwo->id)->count());
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

    public function test_pending_instructors_are_listed_above_active_instructors(): void
    {
        $dean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'pending']);
        User::factory()->create(['role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active']);

        $this->actingAs($dean)
            ->get(route('dean.instructors.index'))
            ->assertOk()
            ->assertSeeInOrder(['Pending Instructor Registrations', 'BSIT Instructor Accounts', 'Search active instructor', 'All employment types']);
    }

    public function test_bsba_subjects_can_use_any_department_room_regardless_of_subject_type(): void
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
            'section_id' => $section->id, 'subject_id' => $subject->id, 'room_id' => $room->id,
        ]);
    }

    public function test_bshm_cooking_subjects_use_kitchen_labs_and_other_subjects_use_lecture_rooms(): void
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

        $this->assertSame($kitchen->id, ClassSchedule::where('subject_id', $cooking->id)->value('room_id'));
        $this->assertSame($lecture->id, ClassSchedule::where('subject_id', $management->id)->value('room_id'));
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
            foreach ([['07:30', '10:00'], ['13:00', '15:30'], ['17:00', '19:30']] as [$start, $end]) {
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
