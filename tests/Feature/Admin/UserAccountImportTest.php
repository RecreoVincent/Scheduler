<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicSection;
use App\Models\StudentRoster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UserAccountImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_dean_gec_instructor_and_student_accounts_from_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        $section = AcademicSection::create([
            'course' => 'BSIT',
            'name' => '1 - East',
            'year_level' => 1,
            'academic_year' => '2026-2027',
            'semester' => 'All',
        ]);
        StudentRoster::create([
            'student_id' => '2026-0001',
            'full_name' => 'Mia Santos',
            'section' => '1 - East',
        ]);
        $csv = implode("\n", [
            'first_name,middle_name,last_name,suffix,email,role,course,employment_type,outside_work_end_time,year_level,section,student_id,account_status,password',
            'Ana,,Reyes,,ana.reyes@example.test,dean,BSIT,,,,,,active,secure-password',
            'Gina,,Cruz,,gina.cruz@example.test,gec,GEC,,,,,,active,secure-password',
            'Ivan,,Cruz,,ivan.cruz@example.test,instructor,BSIT,full_time,,,,,active,secure-password',
            'Mia,,Santos,,mia.santos@example.test,student,BSIT,,,1,1 - East,2026-0001,active,secure-password',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.users.import'), [
                'csv_file' => UploadedFile::fake()->createWithContent('user-accounts.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'ana.reyes@example.test',
            'role' => 'dean',
            'course' => 'BSIT',
            'account_status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'ivan.cruz@example.test',
            'role' => 'instructor',
            'employment_type' => 'full_time',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'gina.cruz@example.test',
            'role' => 'gec',
            'course' => 'GEC',
            'account_status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'mia.santos@example.test',
            'role' => 'student',
            'student_id' => '2026-0001',
            'year_level' => 1,
            'academic_section_id' => $section->id,
        ]);
        $this->assertSame(
            'BSIT',
            User::query()->where('email', 'mia.santos@example.test')->firstOrFail()->department?->code,
        );

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('gina.cruz@example.test')
            ->assertSee('value="gec"', false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('GEC Status')
            ->assertViewHas('statistics', fn (array $statistics): bool => $statistics['total_gec'] === 1);
    }

    public function test_admin_can_download_the_user_account_import_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Import Accounts')
            ->assertSee('Download CSV Template');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.import-template'))
            ->assertOk()
            ->assertDownload('user-account-import-template.csv');
    }

    public function test_user_account_form_has_independent_password_visibility_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        $user = User::factory()->create(['role' => 'dean', 'account_status' => 'active']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index', ['edit' => $user->id]))
            ->assertOk()
            ->assertSeeInOrder([
                'id="user_password"',
                'data-password-eye-toggle',
                'id="user_password_confirmation"',
                'data-password-eye-toggle',
            ], false)
            ->assertSee('Dean / Program Head');
    }
}
