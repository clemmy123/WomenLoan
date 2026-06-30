<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_home_redirects_authenticated_users_to_dashboard(): void
    {
        $this->seedApplication();
        $this->actingAsRole('admin@wdf.go.tz');

        $response = $this->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
