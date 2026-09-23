<?php

namespace Tests\Feature;

use App\Models\Ms365StudentAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class Ms365StudentAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_a_utf8_bom_ms365_csv_with_student_numbers(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        $csv = "\xEF\xBB\xBF\"Display name\",\"User principal name\",\"Student Number\",\"First name\",\"Last name\",\"Block credential\"\n"
            . "\"Maria Santos\",\"maria.santos@mcc.edu.ph\",\"2026-0019\",\"Maria\",\"Santos\",\"False\"\n";

        $this->actingAs($admin)
            ->post(route('admin.ms365-accounts.import'), [
                'csv_file' => UploadedFile::fake()->createWithContent('ms365-users.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ms365_student_accounts', [
            'email' => 'maria.santos@mcc.edu.ph',
            'student_number' => '2026-0019',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ms365-accounts.index', ['search' => '2026-0019']))
            ->assertOk()
            ->assertSee('maria.santos@mcc.edu.ph')
            ->assertSee('2026-0019');
    }

    public function test_admin_can_create_update_and_delete_an_ms365_registry_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.ms365-accounts.store'), [
                'display_name' => 'Ana Reyes',
                'student_number' => '2026-0100',
                'email' => 'ANA.REYES@MCCLAWS.EDU.PH',
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
                'license' => 'Office 365 A1 for students',
                'status' => 'blocked',
            ])
            ->assertRedirect(route('admin.ms365-accounts.index'));

        $account = Ms365StudentAccount::where('email', 'ana.reyes@mcclaws.edu.ph')->firstOrFail();
        $this->assertTrue($account->is_blocked);

        $this->actingAs($admin)
            ->get(route('admin.ms365-accounts.index', ['edit' => $account->id]))
            ->assertOk()
            ->assertSee('Edit MS365 Record')
            ->assertSee('Ana Reyes');

        $this->actingAs($admin)
            ->patch(route('admin.ms365-accounts.update', $account), [
                'display_name' => 'Ana Marie Reyes',
                'student_number' => '2026-0100',
                'email' => 'ana.reyes@mcclaws.edu.ph',
                'first_name' => 'Ana Marie',
                'last_name' => 'Reyes',
                'license' => 'Office 365 A1 for students',
                'status' => 'eligible',
            ])
            ->assertRedirect(route('admin.ms365-accounts.index'));

        $this->assertDatabaseHas('ms365_student_accounts', [
            'id' => $account->id,
            'display_name' => 'Ana Marie Reyes',
            'is_blocked' => false,
            'soft_deleted_at' => null,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.ms365-accounts.destroy', $account))
            ->assertRedirect(route('admin.ms365-accounts.index'));

        $this->assertDatabaseMissing('ms365_student_accounts', ['id' => $account->id]);
    }
}
