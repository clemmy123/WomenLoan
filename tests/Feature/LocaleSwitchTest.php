<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_switch_redirects_to_requested_path(): void
    {
        $response = $this->get('/locale/sw?redirect=/login');

        $response->assertRedirect('/login');
        $this->assertSame('sw', session('locale'));
    }

    public function test_locale_switch_rejects_external_redirect(): void
    {
        $response = $this->get('/locale/en?redirect=//evil.test/phish');

        $response->assertRedirect();
        $this->assertStringNotContainsString('evil.test', $response->headers->get('Location'));
    }
}
