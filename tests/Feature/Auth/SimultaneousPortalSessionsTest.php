<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SimultaneousPortalSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_role_portals_can_remain_open_in_the_same_browser_session(): void
    {
        $users = collect(['admin', 'dean', 'instructor', 'student'])->mapWithKeys(
            fn (string $role): array => [$role => User::factory()->create([
                'role' => $role,
                'course' => 'BSIT',
                'account_status' => 'active',
            ])],
        );

        foreach ($users as $role => $user) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
                'role' => $role,
                'course' => 'BSIT',
            ])->assertRedirect(route("{$role}.dashboard"));

            $this->assertSame($user->id, Auth::guard($role)->id());
        }

        foreach ($users as $role => $user) {
            $this->get(route("{$role}.dashboard"))
                ->assertOk()
                ->assertSee($user->name);
        }

        $this->post(route('logout'), ['role' => 'dean'])->assertRedirect('/');

        $this->assertFalse(Auth::guard('dean')->check());
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertTrue(Auth::guard('instructor')->check());
        $this->assertTrue(Auth::guard('student')->check());
        $this->get(route('dean.dashboard'))->assertRedirect(route('home').'#portals');
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('instructor.dashboard'))->assertOk();
        $this->get(route('student.dashboard'))->assertOk();
    }
}
