<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ms365StudentAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_ms365_accounts_page_is_not_available_in_the_administrator_portal(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);

        $this->actingAs($admin)
            ->get('/admin/ms365-accounts')
            ->assertNotFound();
    }
}
