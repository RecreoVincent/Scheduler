<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AcademicSection;
use App\Models\AcademicTerm;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizedAcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_codes_are_normalized_without_changing_legacy_model_input(): void
    {
        $user = User::factory()->create(['course' => 'BSIT']);
        $room = Room::create(['course' => 'BSIT', 'name' => 'Normalization Lab', 'room_type' => 'Laboratory']);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'NORM 101', 'name' => 'Data Normalization',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 1,
            'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);

        $department = Department::where('code', 'BSIT')->firstOrFail();

        $this->assertSame($department->id, $user->department_id);
        $this->assertSame($department->id, $room->department_id);
        $this->assertSame($department->id, $subject->department_id);
        $this->assertSame(AcademicTerm::where('code', '1st')->value('id'), $subject->academic_term_id);
    }

    public function test_sections_and_schedules_share_a_normalized_academic_period(): void
    {
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - Normalized', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => '1st',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT', 'code' => 'NORM 102', 'name' => 'Relational Design',
            'subject_type' => 'Lecture', 'classification' => 'Major', 'year_level' => 1,
            'semester' => '1st', 'curriculum' => 'New', 'units' => 3,
        ]);
        $instructor = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT']);
        $schedule = ClassSchedule::create([
            'course' => 'BSIT', 'section_id' => $section->id, 'subject_id' => $subject->id,
            'instructor_id' => $instructor->id, 'room_id' => null,
            'academic_year' => '2026-2027', 'semester' => '1st', 'day' => 'M - W',
            'start_time' => '07:30:00', 'end_time' => '10:00:00',
        ]);

        $this->assertNotNull($section->academic_period_id);
        $this->assertSame($section->academic_period_id, $schedule->academic_period_id);
        $this->assertSame(1, AcademicPeriod::where('academic_year', '2026-2027')->count());
    }
}
