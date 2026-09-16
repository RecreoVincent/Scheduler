<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_room_scanner_can_request_camera_access_from_this_site(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=(self)');
    }
}
