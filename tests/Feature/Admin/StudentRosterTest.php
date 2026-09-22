<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicSection;
use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_student_roster(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        StudentRoster::create(['student_id' => '2026-0001', 'full_name' => 'Juan Dela Cruz', 'section' => 'BSIT 1A']);

        $this->actingAs($admin)
            ->get(route('admin.student-roster.index'))
            ->assertOk()
            ->assertSee('Student ID Roster')
            ->assertSee('2026-0001')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('BSIT 1A');
    }

    public function test_admin_can_import_a_student_roster_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $csv = "Student ID,Name,Section\n2026-0001,Juan Dela Cruz,BSIT 1A\n2026-0002,Maria Santos,BSIT 1B\n";
        $file = UploadedFile::fake()->createWithContent('roster.csv', $csv);

        $this->actingAs($admin)
            ->post(route('admin.student-roster.import'), ['csv_file' => $file])
            ->assertRedirect();

        $this->assertDatabaseHas('student_rosters', ['student_id' => '2026-0001', 'full_name' => 'Juan Dela Cruz', 'section' => 'BSIT 1A']);
        $this->assertDatabaseHas('student_rosters', ['student_id' => '2026-0002', 'full_name' => 'Maria Santos', 'section' => 'BSIT 1B']);
    }

    public function test_reimporting_updates_an_existing_student_id_instead_of_duplicating(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        StudentRoster::create(['student_id' => '2026-0001', 'full_name' => 'Old Name', 'section' => 'BSIT 1A']);

        $csv = "Student ID,Name,Section\n2026-0001,Updated Name,BSIT 1B\n";
        $file = UploadedFile::fake()->createWithContent('roster.csv', $csv);

        $this->actingAs($admin)->post(route('admin.student-roster.import'), ['csv_file' => $file]);

        $this->assertSame(1, StudentRoster::where('student_id', '2026-0001')->count());
        $this->assertDatabaseHas('student_rosters', ['student_id' => '2026-0001', 'full_name' => 'Updated Name', 'section' => 'BSIT 1B']);
    }

    public function test_import_uses_the_matching_dean_section_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $file = UploadedFile::fake()->createWithContent('roster.csv', "Student ID,Name,Section\n2026-0001,Juan Dela Cruz,1-East\n");

        $this->actingAs($admin)
            ->post(route('admin.student-roster.import'), ['csv_file' => $file])
            ->assertRedirect();

        $this->assertDatabaseHas('student_rosters', [
            'student_id' => '2026-0001',
            'section' => '1 - East',
        ]);
    }

    public function test_import_assigns_students_to_the_department_in_each_csv_row(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bsitDean = User::factory()->create(['role' => 'dean', 'course' => 'BSIT']);
        AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $bsbaSection = AcademicSection::create([
            'course' => 'BSBA', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $existingStudent = User::factory()->create([
            'role' => 'student', 'student_id' => '2026-0001', 'course' => 'BSIT',
            'year_level' => 1, 'academic_section_id' => null,
        ]);
        $file = UploadedFile::fake()->createWithContent('roster.csv', implode("\n", [
            'Student ID,Name,Department,Section',
            '2026-0001,Juan Dela Cruz,BSBA,1-East',
            '2026-0002,Maria Santos,BSIT,1-East',
        ]));

        $this->actingAs($admin)
            ->post(route('admin.student-roster.import'), ['csv_file' => $file])
            ->assertRedirect();

        $this->assertDatabaseHas('student_rosters', [
            'student_id' => '2026-0001',
            'course' => 'BSBA',
            'section' => '1 - East',
        ]);
        $this->assertDatabaseHas('student_rosters', [
            'student_id' => '2026-0002',
            'course' => 'BSIT',
            'section' => '1 - East',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $existingStudent->id,
            'course' => 'BSBA',
            'year_level' => 1,
            'academic_section_id' => $bsbaSection->id,
        ]);
        $this->assertDatabaseHas('users', [
            'student_id' => '2026-0002',
            'role' => 'student',
            'course' => 'BSIT',
            'account_status' => 'inactive',
        ]);

        $this->actingAs($bsitDean)
            ->get(route('dean.students.index'))
            ->assertOk()
            ->assertSee('Maria Santos')
            ->assertSee('Inactive');
    }

    public function test_import_requires_a_department_when_a_section_name_is_shared_by_departments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['BSIT', 'BSBA'] as $course) {
            AcademicSection::create([
                'course' => $course, 'name' => '1 - East', 'year_level' => 1,
                'academic_year' => '2026-2027', 'semester' => 'All',
            ]);
        }
        $file = UploadedFile::fake()->createWithContent(
            'roster.csv',
            "Student ID,Name,Section\n2026-0001,Juan Dela Cruz,1-East\n",
        );

        $this->actingAs($admin)
            ->post(route('admin.student-roster.import'), ['csv_file' => $file])
            ->assertRedirect()
            ->assertSessionHas('error_note');

        $this->assertDatabaseMissing('student_rosters', ['student_id' => '2026-0001']);
    }
}
