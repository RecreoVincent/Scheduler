<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDeletedAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_restore_a_recently_deleted_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'first_name' => 'Recently',
            'last_name' => 'Deleted',
            'role' => 'student',
            'course' => 'BSIT',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.deleted'))
            ->assertOk()
            ->assertSee('Recently Deleted Accounts')
            ->assertSee('Recently Deleted')
            ->assertSee('Restore');

        $this->actingAs($admin)
            ->patch(route('admin.users.restore', $user->id))
            ->assertRedirect(route('admin.users.deleted'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_permanently_delete_a_recently_deleted_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'instructor', 'course' => 'BSIT']);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.deleted'))
            ->assertOk()
            ->assertSee('Delete Permanently');

        $this->actingAs($admin)
            ->delete(route('admin.users.force-delete', $user->id))
            ->assertRedirect(route('admin.users.deleted'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_force_delete_an_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $otherAdmin->delete();

        $this->actingAs($admin)
            ->delete(route('admin.users.force-delete', $otherAdmin->id))
            ->assertForbidden();

        $this->assertSoftDeleted('users', ['id' => $otherAdmin->id]);
    }
}
