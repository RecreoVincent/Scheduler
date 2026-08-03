<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('height:100dvh', false)
            ->assertSee('overflow:hidden', false)
            ->assertSee('@media(max-height:850px)', false);
    }

    public function test_bsit_dean_login_uses_department_branding(): void
    {
        $this->get(route('login', ['role' => 'dean', 'course' => 'BSIT']))
            ->assertOk()
            ->assertSee('class="branded-portal-login"', false)
            ->assertSee('images/landing-background.png')
            ->assertSee('images/bsit-department-logo.jpg')
            ->assertSee('bsit-logo-wrap')
            ->assertSee('bsit-portal-symbol')
            ->assertSee('Information Technology Department logo')
            ->assertSee('Welcome, BSIT Dean')
            ->assertSee('Plan the department with clarity.')
            ->assertSee('value="dean"', false)
            ->assertSee('value="BSIT"', false);
    }

    public function test_each_dean_department_uses_its_department_branding(): void
    {
        $departments = [
            'BSBA' => ['Business Administration Department', 'images/bsba-department-logo.jpg'],
            'BSHM' => ['Hospitality Management Department', 'images/bshm-department-logo.jpg'],
            'BSED' => ['Secondary Education Department', 'images/education-department-logo.jpg'],
            'BEED' => ['Elementary Education Department', 'images/education-department-logo.jpg'],
        ];

        foreach ($departments as $course => [$departmentName, $logo]) {
            $this->get(route('login', ['role' => 'dean', 'course' => $course]))
                ->assertOk()
                ->assertSee('class="branded-portal-login"', false)
                ->assertSee($logo)
                ->assertSee('department-logo-wrap')
                ->assertSee('department-portal-symbol')
                ->assertSee('department-logo-'.strtolower($course))
                ->assertSee($departmentName.' logo')
                ->assertSee('Welcome, '.$course.' Dean')
                ->assertSee('value="'.$course.'"', false);
        }
    }

    public function test_admin_instructor_and_student_logins_use_mcc_branding(): void
    {
        $roles = [
            'admin' => ['Welcome, Administrator', 'MCC Administration Portal'],
            'instructor' => ['Welcome, Instructor', 'MCC Instructor Portal'],
            'student' => ['Welcome, Student', 'MCC Student Portal'],
        ];

        foreach ($roles as $role => [$welcome, $label]) {
            $this->get(route('login', ['role' => $role]))
                ->assertOk()
                ->assertSee('class="branded-portal-login"', false)
                ->assertSee('images/landing-background.png')
                ->assertSee('images/mcc-college-logo.png')
                ->assertSee('Madridejos Community College logo')
                ->assertSee('mcc-logo-wrap')
                ->assertSee('mcc-portal-symbol')
                ->assertSee($welcome)
                ->assertSee($label)
                ->assertSee('value="'.$role.'"', false);
        }
    }

    public function test_unselected_login_keeps_the_fallback_design(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('class="branded-portal-login"', false)
            ->assertSee('Welcome back');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_pending_users_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'course' => 'BSIT',
            'account_status' => 'pending',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'student',
            'course' => 'BSIT',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
