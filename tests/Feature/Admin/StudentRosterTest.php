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
}
