<?php

namespace Tests\Feature\Auth;

use App\Models\AcademicSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200)
            ->assertSee('Join your academic workspace')
            ->assertSee('name="academic_section_id"', false);
    }

    public function test_new_users_can_register(): void
    {
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'course' => 'BSIT',
            'year_level' => 1,
            'academic_section_id' => $section->id,
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login', ['role' => 'student', 'course' => 'BSIT']));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'student',
            'course' => 'BSIT',
            'year_level' => 1,
            'academic_section_id' => $section->id,
            'account_status' => 'active',
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'student',
            'course' => 'BSIT',
        ])->assertRedirect(route('student.dashboard'));
    }

    public function test_student_cannot_register_with_a_section_from_another_department_or_year(): void
    {
        $section = AcademicSection::create([
            'course' => 'BSBA', 'name' => '2 - North', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        $this->from(route('register', ['role' => 'student']))->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'email' => 'student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'course' => 'BSIT',
            'year_level' => 1,
            'academic_section_id' => $section->id,
        ])->assertRedirect(route('register', ['role' => 'student']))
            ->assertSessionHasErrors('academic_section_id');

        $this->assertDatabaseMissing('users', ['email' => 'student@example.com']);
    }

    public function test_dean_and_instructor_registration_forms_use_the_same_login_theme(): void
    {
        $this->get(route('register', ['role' => 'dean', 'course' => 'BSIT']))
            ->assertOk()
            ->assertSee('Dean Portal')
            ->assertSee('#450693', false)
            ->assertDontSee('name="academic_section_id"', false);

        $this->get(route('register', ['role' => 'instructor', 'course' => 'BSIT']))
            ->assertOk()
            ->assertSee('Instructor Portal')
            ->assertSee('name="employment_type"', false)
            ->assertDontSee('name="academic_section_id"', false);
    }
}
