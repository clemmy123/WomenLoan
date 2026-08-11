<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_landing_for_guests_when_jumuishi_disabled(): void
    {
        config(['jumuishi.enabled' => false]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('home');
    }

    public function test_home_redirects_authenticated_users_to_their_accessible_home(): void
    {
        $this->seedApplication();
        $this->actingAsRole('admin@wdf.go.tz');

        $response = $this->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
