<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_portal_renders_the_shared_hamburger_sidebar_controls(): void
    {
        foreach ([
            ['admin', 'admin.dashboard'],
            ['dean', 'dean.dashboard'],
            ['instructor', 'instructor.dashboard'],
            ['student', 'student.dashboard'],
        ] as [$role, $route]) {
            $user = User::factory()->create([
                'role' => $role,
                'course' => 'BSIT',
                'account_status' => 'active',
            ]);

            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('MCC | Scheduler')
                ->assertSee('--primary:#450693', false)
                ->assertSee('images/landing-background.png')
                ->assertSee('--portal-panel-image:', false)
                ->assertSee('background:#fff;', false)
                ->assertSee('background-image:var(--portal-panel-image)', false)
                ->assertSee('.app,.main,.content { background:#fff; }', false)
                ->assertSee('.page-header p { color:var(--navy)', false)
                ->assertSee('.profile-avatar {', false)
                ->assertSee('.profile .profile-avatar { color:#fff', false)
                ->assertSee('id="sidebarToggle"', false)
                ->assertSee('aria-controls="portalSidebar"', false)
                ->assertSee('aria-expanded="false"', false)
                ->assertSee('id="portalSidebar"', false)
                ->assertSee('id="sidebarBackdrop"', false)
                ->assertSee('body.sidebar-open .sidebar-toggle', false)
                ->assertSee("backdrop.addEventListener('click', closeSidebar)", false);
        }
    }

    public function test_portals_render_the_correct_sidebar_brand_logo(): void
    {
        foreach ([
            ['admin', 'admin.dashboard', 'images/mcc-college-logo.png'],
            ['dean', 'dean.dashboard', 'images/bsit-department-logo.jpg'],
            ['instructor', 'instructor.dashboard', 'images/bsit-department-logo.jpg'],
            ['student', 'student.dashboard', 'images/bsit-department-logo.jpg'],
        ] as [$role, $route, $logo]) {
            $user = User::factory()->create([
                'role' => $role,
                'course' => 'BSIT',
                'account_status' => 'active',
            ]);

            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSee($logo);
        }
    }

    public function test_department_portal_members_render_their_assigned_department_logo(): void
    {
        $departments = [
            'BSIT' => 'images/bsit-department-logo.jpg',
            'BSBA' => 'images/bsba-department-logo.jpg',
            'BSHM' => 'images/bshm-department-logo.jpg',
            'BSED' => 'images/education-department-logo.jpg',
            'BEED' => 'images/education-department-logo.jpg',
        ];

        foreach ($departments as $course => $logo) {
            foreach ([
                ['dean', 'dean.dashboard'],
                ['instructor', 'instructor.dashboard'],
                ['student', 'student.dashboard'],
            ] as [$role, $route]) {
                $user = User::factory()->create([
                    'role' => $role,
                    'course' => $course,
                    'account_status' => 'active',
                ]);

                $this->actingAs($user)
                    ->get(route($route))
                    ->assertOk()
                    ->assertSee($logo)
                    ->assertSee($course.' department logo');
            }
        }
    }
}
