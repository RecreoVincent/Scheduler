<?php

namespace Tests\Feature\Auth;

use App\Models\AcademicSection;
use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentIdLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_id_starts_registration_then_requires_gmail_otp_before_the_account_is_created(): void
    {
        Mail::fake();
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - West', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);
        StudentRoster::create([
            'student_id' => '2026-0001', 'full_name' => 'Juan Dela Cruz',
            'course' => 'BSIT', 'section' => '1 - West',
        ]);

        $this->get(route('login', ['role' => 'student']))
            ->assertOk()
            ->assertSee('Student ID')
            ->assertSee('action="'.route('login.identify').'"', false)
            ->assertDontSee('Last name');

        $this->post(route('login.identify'), ['role' => 'student', 'portal_id' => '2026-0001'])
            ->assertRedirect(route('register', ['role' => 'student', 'portal_id' => '2026-0001']));

        $this->get(route('register', ['role' => 'student', 'portal_id' => '2026-0001']))
            ->assertOk()
            ->assertSee('Student ID')
            ->assertSee('Username')
            ->assertSee('Gmail Address')
            ->assertSee('Create Account');

        $this->post(route('register'), [
            'role' => 'student',
            'username' => 'juan.delacruz',
            'email' => 'juan.delacruz@gmail.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertRedirect(route('register.otp'));

        $this->assertDatabaseMissing('users', ['student_id' => '2026-0001', 'username' => 'juan.delacruz']);
        $pending = session('portal_registration_otp');
        $pending['otp_hash'] = Hash::make('123456');

        $this->withSession(['portal_registration_otp' => $pending])
            ->post(route('register.otp.verify'), ['otp' => '123456'])
            ->assertRedirect(route('login', [
                'role' => 'student',
                'portal_id' => '2026-0001',
                'step' => 'sign-in',
            ]));

        $student = User::where('student_id', '2026-0001')->firstOrFail();
        $this->assertSame('juan.delacruz', $student->username);
        $this->assertSame('juan.delacruz@gmail.com', $student->email);
        $this->assertSame('active', $student->account_status);
        $this->assertSame($section->id, $student->academic_section_id);

        $this->post(route('login'), [
            'role' => 'student',
            'portal_id' => '2026-0001',
            'password' => 'password1',
        ])->assertRedirect(route('student.login-transition'));

        $this->assertAuthenticatedAs($student, 'student');
    }

    public function test_instructor_id_starts_registration_then_requires_gmail_otp_before_sign_in(): void
    {
        Mail::fake();
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'account_status' => 'active',
            'instructor_id' => '2026-0042',
        ]);

        $this->post(route('login.identify'), ['role' => 'instructor', 'portal_id' => $instructor->instructor_id])
            ->assertRedirect(route('register', ['role' => 'instructor', 'portal_id' => '2026-0042']));

        $this->post(route('register'), [
            'role' => 'instructor',
            'username' => 'instructor.account',
            'email' => 'instructor.account@gmail.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertRedirect(route('register.otp'));

        $pending = session('portal_registration_otp');
        $pending['otp_hash'] = Hash::make('123456');
        $this->withSession(['portal_registration_otp' => $pending])
            ->post(route('register.otp.verify'), ['otp' => '123456'])
            ->assertRedirect(route('login', [
                'role' => 'instructor',
                'portal_id' => '2026-0042',
                'step' => 'sign-in',
            ]));

        $instructor->refresh();
        $this->assertSame('instructor.account', $instructor->username);
        $this->assertSame('instructor.account@gmail.com', $instructor->email);

        $this->post(route('login'), [
            'role' => 'instructor',
            'portal_id' => '2026-0042',
            'password' => 'password1',
        ])->assertRedirect(route('instructor.login-transition'));

        $this->assertAuthenticatedAs($instructor, 'instructor');
    }

    public function test_registered_id_continues_to_the_password_step_and_unknown_id_is_rejected(): void
    {
        $registeredInstructor = User::factory()->create([
            'role' => 'instructor', 'course' => 'BSIT', 'account_status' => 'active',
            'instructor_id' => '2026-0042', 'username' => 'already.registered',
        ]);

        $this->post(route('login.identify'), ['role' => 'instructor', 'portal_id' => $registeredInstructor->instructor_id])
            ->assertRedirect(route('login', [
                'role' => 'instructor',
                'portal_id' => '2026-0042',
                'step' => 'sign-in',
            ]));

        $this->from(route('login', ['role' => 'student']))
            ->post(route('login.identify'), ['role' => 'student', 'portal_id' => '2026-9999'])
            ->assertRedirect(route('login', ['role' => 'student']))
            ->assertSessionHasErrors('portal_id');
    }
}
