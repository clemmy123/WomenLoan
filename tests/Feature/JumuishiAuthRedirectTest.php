<?php

namespace Tests\Feature;

use App\Services\JumuishiUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JumuishiAuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jumuishi.enabled' => true,
            'jumuishi.url' => 'http://127.0.0.1:8000',
            'jumuishi.module_path' => 'women-loans',
            'jumuishi.sso_start_path' => '/sso/start',
            'jumuishi.central_logout_path' => '/central-logout',
            'jumuishi.forgot_password_path' => '/forgot-password',
        ]);
    }

    public function test_guest_home_redirects_to_jumuishi(): void
    {
        $this->get('/')
            ->assertRedirect('http://127.0.0.1:8000');
    }

    public function test_login_redirects_to_jumuishi_sso_start(): void
    {
        $this->get(route('login'))
            ->assertRedirect('http://127.0.0.1:8000/sso/start/women-loans?return_to=%2F');
    }

    public function test_login_post_does_not_authenticate_locally(): void
    {
        $this->seedApplication();

        $this->post(route('login'), [
            'email' => 'ministry@wdf.go.tz',
            'password' => 'password',
        ])->assertRedirect('http://127.0.0.1:8000/sso/start/women-loans?return_to=%2F');

        $this->assertGuest();
    }

    public function test_register_redirects_to_jumuishi(): void
    {
        $this->get(route('register'))
            ->assertRedirect('http://127.0.0.1:8000');
    }

    public function test_forgot_password_redirects_to_jumuishi(): void
    {
        $this->get(route('password.request'))
            ->assertRedirect('http://127.0.0.1:8000/forgot-password');
    }

    public function test_protected_page_redirects_guest_to_sso_with_return_to(): void
    {
        $this->get('/dashboard')
            ->assertRedirect(JumuishiUrl::ssoStart('/dashboard'));
    }

    public function test_unauthenticated_json_returns_401(): void
    {
        $this->getJson('/dashboard')
            ->assertUnauthorized();
    }

    public function test_logout_goes_to_central_logout(): void
    {
        $this->seedApplication();
        $this->actingAsRole('ministry@wdf.go.tz');

        $this->post(route('logout'))
            ->assertRedirect('http://127.0.0.1:8000/central-logout');

        $this->assertGuest();
    }
}
