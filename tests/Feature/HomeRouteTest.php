<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_landing_page_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(__('home.portal_name'), false);
        $response->assertSee(__('home.headline'), false);
        $response->assertSee(__('home.footer_copyright'), false);
    }

    public function test_home_redirects_authenticated_users_to_dashboard(): void
    {
        $this->seedApplication();
        $this->actingAsRole('admin@wdf.go.tz');

        $response = $this->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
