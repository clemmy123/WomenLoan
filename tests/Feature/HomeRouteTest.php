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
        $response->assertSee('css/bootstrap-layer.css', false);
        $response->assertSee('css/app.css', false);
        $response->assertSee('css/wdf-mvc.css', false);
        $response->assertDontSee('/build/assets/', false);
    }

    public function test_home_redirects_authenticated_users_to_their_accessible_home(): void
    {
        $this->seedApplication();
        $this->actingAsRole('admin@wdf.go.tz');

        $response = $this->get('/');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_sidebar_uses_compact_brand_and_wdf_menus(): void
    {
        $this->seedApplication();

        $response = $this->actingAsRole('admin@wdf.go.tz')->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('sidebar-brand', false);
        $response->assertSee('sidebar-home-pill', false);
        $response->assertSee('dashboard-stats-grid', false);
        $response->assertSee('dashboard-charts-grid', false);
        $response->assertSee(__('nav.home'), false);
        $response->assertSee(__('nav.welcome'), false);
        $response->assertSee(__('nav.dashboard'), false);
        $response->assertSee('sidebar-tree', false);
        $response->assertSee('sidebar-submenu', false);
        $response->assertDontSee('tracking-widest', false);
    }
}
