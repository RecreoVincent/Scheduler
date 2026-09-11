<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_password_can_be_changed_from_the_profile_form(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-password', $user->refresh()->password));
    }

    public function test_password_is_unchanged_when_password_fields_are_left_blank(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $originalHash = $user->password;

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $this->assertSame($originalHash, $user->refresh()->password);
    }

    public function test_password_change_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $originalHash = $user->password;

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertSessionHasErrors('current_password')->assertRedirect('/profile');

        $this->assertSame($originalHash, $user->refresh()->password);
    }

    public function test_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $originalHash = $user->password;

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'does-not-match',
            ]);

        $response->assertSessionHasErrors('password')->assertRedirect('/profile');

        $this->assertSame($originalHash, $user->refresh()->password);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
