<?php

namespace Tests\Feature\Auth;

use App\Models\AcademicSection;
use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIdLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_student_can_sign_in_using_student_number_and_last_name(): void
    {
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - West', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        StudentRoster::create([
            'student_id' => '2026-0001',
            'full_name' => 'Juan Dela Cruz',
            'section' => '1 - West',
        ]);

        $this->post(route('login.student'), [
            'student_id' => '2026-0001',
            'last_name' => 'dela cruz',
        ])->assertRedirect(route('student.login-transition'));

        $student = User::query()->where('student_id', '2026-0001')->firstOrFail();
        $this->assertAuthenticatedAs($student, 'student');
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'role' => 'student',
            'course' => 'BSIT',
            'year_level' => 1,
            'academic_section_id' => $section->id,
            'account_status' => 'active',
            'last_name' => 'Dela Cruz',
        ]);
        $this->assertNotNull($student->fresh()->last_login_at);
        $this->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('BSIT · Year 1')
            ->assertSee('Section 1 - West');
    }

    public function test_student_cannot_sign_in_when_the_roster_entry_or_last_name_does_not_match(): void
    {
        StudentRoster::create(['student_id' => '2026-0001', 'full_name' => 'Juan Dela Cruz']);

        $this->from(route('login', ['role' => 'student']))
            ->post(route('login.student'), ['student_id' => '2026-0001', 'last_name' => 'Santos'])
            ->assertRedirect(route('login', ['role' => 'student']))
            ->assertSessionHasErrors('student_id');
        $this->assertGuest('student');

        $this->from(route('login', ['role' => 'student']))
            ->post(route('login.student'), ['student_id' => '2026-9999', 'last_name' => 'Dela Cruz'])
            ->assertRedirect(route('login', ['role' => 'student']))
            ->assertSessionHasErrors('student_id');
        $this->assertGuest('student');
    }

    public function test_existing_student_account_is_activated_after_roster_verification(): void
    {
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - West', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        StudentRoster::create(['student_id' => '2026-0001', 'full_name' => 'Juan Dela Cruz', 'section' => '1-west']);
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => '2026-0001',
            'course' => 'BSBA',
            'year_level' => 4,
            'academic_section_id' => null,
            'account_status' => 'pending',
        ]);

        $this->post(route('login.student'), ['student_id' => '2026-0001', 'last_name' => 'Cruz'])
            ->assertRedirect(route('student.login-transition'));

        $this->assertAuthenticatedAs($student, 'student');
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'account_status' => 'active',
            'course' => 'BSIT',
            'year_level' => 1,
            'academic_section_id' => $section->id,
        ]);
    }

    public function test_roster_department_prevents_an_identical_section_name_from_assigning_the_wrong_department(): void
    {
        AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        $bsbaSection = AcademicSection::create([
            'course' => 'BSBA', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        StudentRoster::create([
            'student_id' => '2026-0002',
            'full_name' => 'Maria Santos',
            'course' => 'BSBA',
            'section' => '1 - East',
        ]);

        $this->post(route('login.student'), ['student_id' => '2026-0002', 'last_name' => 'Santos'])
            ->assertRedirect(route('student.login-transition'));

        $this->assertDatabaseHas('users', [
            'student_id' => '2026-0002',
            'course' => 'BSBA',
            'year_level' => 1,
            'academic_section_id' => $bsbaSection->id,
        ]);
    }

    public function test_student_login_screen_uses_roster_credentials_without_registration_or_password_fields(): void
    {
        $this->get(route('login', ['role' => 'student']))
            ->assertOk()
            ->assertSee('Student number')
            ->assertSee('Last name')
            ->assertSee('action="'.route('login.student').'"', false)
            ->assertDontSee('Register here');
    }
}
