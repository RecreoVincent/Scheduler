<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertDontSee('Academic scheduling')
            ->assertSee('Choose your portal')
            ->assertSee('images/landing-background.png')
            ->assertSee('height: 100dvh', false)
            ->assertSee('overflow: hidden', false)
            ->assertSee('images/mcc-college-logo.png')
            ->assertSee('Madridejos Community College logo')
            ->assertSee('MCC | Scheduler')
            ->assertSee('An Automated Class Scheduling and Room Allocation System')
            ->assertSee(route('login', ['role' => 'admin']))
            ->assertSee(route('login', ['role' => 'instructor']))
            ->assertSee(route('login', ['role' => 'student']))
            ->assertSee('id="openCourses"', false)
            ->assertSee('id="courseModal"', false)
            ->assertSee(route('login', ['role' => 'dean', 'course' => 'BSIT']))
            ->assertSee('images/bsit-department-logo.jpg')
            ->assertSee('images/bsba-department-logo.jpg')
            ->assertSee('images/bshm-department-logo.jpg')
            ->assertSee('images/education-department-logo.jpg')
            ->assertSee('BSED department logo')
            ->assertSee('BEED department logo');
    }

    public function test_portal_pagination_uses_the_shared_numbered_button_design(): void
    {
        $paginator = new LengthAwarePaginator(
            items: range(11, 20),
            total: 30,
            perPage: 10,
            currentPage: 2,
            options: ['path' => '/records'],
        );

        $html = Blade::render(
            '<x-pagination :paginator="$paginator" label="Record pages" />',
            compact('paginator'),
        );

        $this->assertStringContainsString('class="portal-pagination"', $html);
        $this->assertStringContainsString('aria-label="Record pages"', $html);
        $this->assertStringContainsString('class="portal-page-button is-active" aria-current="page">2</span>', $html);
        $this->assertStringContainsString('href="/records?page=1"', $html);
        $this->assertStringContainsString('href="/records?page=3"', $html);
        $this->assertStringContainsString('aria-label="Previous page"', $html);
        $this->assertStringContainsString('aria-label="Next page"', $html);
    }
}
