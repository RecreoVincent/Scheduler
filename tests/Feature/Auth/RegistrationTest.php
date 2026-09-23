<?php

namespace Tests\Feature\Auth;

use App\Models\AcademicSection;
use App\Models\Ms365StudentAccount;
use App\Models\StudentRoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        Mail::fake();
        Ms365StudentAccount::create(['email'=>'test@example.com','display_name'=>'Test User']);
        StudentRoster::create(['student_id'=>'2026-0001','full_name'=>'Test User']);
        $section = AcademicSection::create([
            'course' => 'BSIT', 'name' => '1 - East', 'year_level' => 1,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'role' => 'student',
            'course' => 'BSIT',
            'student_id' => '2026-0001',
            'year_level' => 1,
            'academic_section_id' => $section->id,
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register.otp'));
        $this->assertDatabaseMissing('users', ['email'=>'test@example.com']);
        $pending = session('student_registration_otp');
        $pending['otp_hash'] = Hash::make('123456');
        $this->withSession(['student_registration_otp'=>$pending])->post(route('register.otp.verify'), ['otp'=>'123456'])
            ->assertRedirect(route('login', ['role'=>'student','course'=>'BSIT']));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'student',
            'course' => 'BSIT',
            'student_id' => '2026-0001',
            'year_level' => 1,
            'academic_section_id' => $section->id,
            'account_status' => 'active',
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password1',
            'role' => 'student',
            'course' => 'BSIT',
        ])->assertRedirect(route('student.dashboard'));
    }

    public function test_student_without_an_eligible_ms365_account_cannot_register(): void
    {
        StudentRoster::create(['student_id'=>'2026-0002','full_name'=>'No Account']);
        $section = AcademicSection::create(['course'=>'BSIT','name'=>'1 - East','year_level'=>1,'academic_year'=>'2026-2027','semester'=>'All']);
        $this->post('/register', ['first_name'=>'No','last_name'=>'Account','email'=>'outside@example.com','password'=>'password1','password_confirmation'=>'password1','role'=>'student','course'=>'BSIT','student_id'=>'2026-0002','year_level'=>1,'academic_section_id'=>$section->id])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users',['email'=>'outside@example.com']);
    }

    public function test_student_without_a_roster_entry_cannot_register(): void
    {
        Ms365StudentAccount::create(['email'=>'notonroster@example.com','display_name'=>'Not On Roster']);
        $section = AcademicSection::create(['course'=>'BSIT','name'=>'1 - East','year_level'=>1,'academic_year'=>'2026-2027','semester'=>'All']);
        $this->post('/register', ['first_name'=>'Not','last_name'=>'OnRoster','email'=>'notonroster@example.com','password'=>'password1','password_confirmation'=>'password1','role'=>'student','course'=>'BSIT','student_id'=>'2026-9999','year_level'=>1,'academic_section_id'=>$section->id])
            ->assertSessionHasErrors('student_id');
        $this->assertDatabaseMissing('users',['email'=>'notonroster@example.com']);
    }

    public function test_student_id_already_registered_cannot_be_reused(): void
    {
        Mail::fake();
        Ms365StudentAccount::create(['email'=>'first@example.com','display_name'=>'First User']);
        Ms365StudentAccount::create(['email'=>'second@example.com','display_name'=>'Second User']);
        StudentRoster::create(['student_id'=>'2026-0003','full_name'=>'First User']);
        $section = AcademicSection::create(['course'=>'BSIT','name'=>'1 - East','year_level'=>1,'academic_year'=>'2026-2027','semester'=>'All']);

        $firstPayload = ['first_name'=>'First','last_name'=>'User','email'=>'first@example.com','password'=>'password1','password_confirmation'=>'password1','role'=>'student','course'=>'BSIT','student_id'=>'2026-0003','year_level'=>1,'academic_section_id'=>$section->id];
        $this->post('/register', $firstPayload)->assertRedirect(route('register.otp'));
        $pending = session('student_registration_otp');
        $pending['otp_hash'] = Hash::make('123456');
        $this->withSession(['student_registration_otp'=>$pending])->post(route('register.otp.verify'), ['otp'=>'123456'])
            ->assertRedirect(route('login', ['role'=>'student','course'=>'BSIT']));

        $secondPayload = ['first_name'=>'Second','last_name'=>'User','email'=>'second@example.com','password'=>'password1','password_confirmation'=>'password1','role'=>'student','course'=>'BSIT','student_id'=>'2026-0003','year_level'=>1,'academic_section_id'=>$section->id];
        $this->post('/register', $secondPayload)->assertSessionHasErrors('student_id');
        $this->assertDatabaseMissing('users',['email'=>'second@example.com']);
    }

    public function test_student_cannot_register_with_a_section_from_another_department_or_year(): void
    {
        Ms365StudentAccount::create(['email'=>'student@example.com','display_name'=>'Test Student']);
        StudentRoster::create(['student_id'=>'2026-0004','full_name'=>'Test Student']);
        $section = AcademicSection::create([
            'course' => 'BSBA', 'name' => '2 - North', 'year_level' => 2,
            'academic_year' => '2026-2027', 'semester' => 'All',
        ]);

        $this->from(route('register', ['role' => 'student']))->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'email' => 'student@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'role' => 'student',
            'course' => 'BSIT',
            'student_id' => '2026-0004',
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
            ->assertSee('Dean / Program Head Portal')
            ->assertSee('#450693', false)
            ->assertDontSee('name="academic_section_id"', false);

        $this->get(route('register', ['role' => 'instructor', 'course' => 'BSIT']))
            ->assertOk()
            ->assertSee('Instructor Portal')
            ->assertSee('name="employment_type"', false)
            ->assertDontSee('name="academic_section_id"', false);
    }
}
