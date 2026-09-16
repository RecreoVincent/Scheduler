<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_user_accounts_keeps_the_admin_in_the_same_session(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('action="/admin/users"', false);

        $this->get('/admin/users?search=sample')
            ->assertOk();

        $this->assertAuthenticatedAs($admin, 'admin');
    }
}
