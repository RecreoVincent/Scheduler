<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_and_student_registration_must_begin_with_the_portal_id(): void
    {
        $this->get(route('register', ['role' => 'instructor']))
            ->assertRedirect(route('login', ['role' => 'instructor']))
            ->assertSessionHas('error');

        $this->get(route('register', ['role' => 'student']))
            ->assertRedirect(route('login', ['role' => 'student']))
            ->assertSessionHas('error');
    }

    public function test_dean_registration_remains_available_without_the_id_first_flow(): void
    {
        $this->get(route('register', ['role' => 'dean', 'course' => 'BSIT']))
            ->assertOk()
            ->assertSee('Dean / Program Head Portal')
            ->assertSee('name="first_name"', false);
    }
}
