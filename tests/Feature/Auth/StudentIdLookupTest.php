<?php

namespace Tests\Feature\Auth;

use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIdLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_student_id_is_reported_as_not_found(): void
    {
        $this->postJson(route('login.student-id'), ['student_id' => '2026-9999'])
            ->assertStatus(404)
            ->assertJson(['status' => 'not_found']);
    }

    public function test_a_roster_student_id_with_no_account_is_reported_as_unregistered(): void
    {
        StudentRoster::create(['student_id' => '2026-0001', 'full_name' => 'Juan Dela Cruz']);

        $this->postJson(route('login.student-id'), ['student_id' => '2026-0001'])
            ->assertOk()
            ->assertJson(['status' => 'unregistered']);
    }

    public function test_a_roster_student_id_with_an_account_is_reported_as_registered_with_its_email(): void
    {
        StudentRoster::create(['student_id' => '2026-0001', 'full_name' => 'Juan Dela Cruz']);
        User::factory()->create([
            'role' => 'student',
            'student_id' => '2026-0001',
            'email' => 'juan@mcclawis.edu.ph',
        ]);

        $this->postJson(route('login.student-id'), ['student_id' => '2026-0001'])
            ->assertOk()
            ->assertJson(['status' => 'registered', 'email' => 'juan@mcclawis.edu.ph']);
    }

    public function test_student_id_is_required(): void
    {
        $this->postJson(route('login.student-id'), [])
            ->assertStatus(422)
            ->assertJsonPath('status', 'invalid');
    }
}
