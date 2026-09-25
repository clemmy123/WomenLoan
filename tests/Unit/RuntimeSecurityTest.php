<?php

namespace Tests\Unit;

use App\Support\JamiiCors;
use App\Support\RuntimeSecurity;
use Illuminate\Http\Request;
use Tests\TestCase;

class RuntimeSecurityTest extends TestCase
{
    public function test_localhost_is_not_treated_as_exposed(): void
    {
        $this->assertFalse(RuntimeSecurity::isExposedHost('http://127.0.0.1:8001'));
        $this->assertFalse(RuntimeSecurity::isExposedHost('http://localhost'));
        $this->assertTrue(RuntimeSecurity::isExposedHost('http://41.59.229.51:8010'));
    }

    public function test_testing_localhost_is_not_locked_down(): void
    {
        $this->assertFalse(RuntimeSecurity::isExposedHost((string) config('app.url')));
    }

    public function test_lockdown_disables_debug_and_fake_nida_on_exposed_hosts(): void
    {
        config([
            'app.debug' => true,
            'app.url' => 'http://41.59.229.51:8010',
            'session.encrypt' => false,
            'services.nida.driver' => 'fake',
            'services.nida.base_url' => '',
            'services.nida.enabled' => true,
        ]);

        $this->assertTrue(RuntimeSecurity::shouldLockDown());

        RuntimeSecurity::apply();

        $this->assertFalse(config('app.debug'));
        $this->assertTrue(config('session.encrypt'));
        $this->assertSame('http', config('services.nida.driver'));
        $this->assertFalse(config('services.nida.enabled'));
    }

    public function test_lockdown_strips_localhost_cors_on_live_hosts(): void
    {
        config([
            'app.url' => 'http://41.59.229.51:8010',
            'jumuishi.url' => 'http://41.59.229.51:8092',
            'services.jamii.cors_origins' => 'http://41.59.229.51:8092,http://127.0.0.1:8000,http://localhost:8000',
            'cors.allowed_origins' => [
                'http://41.59.229.51:8092',
                'http://127.0.0.1:8000',
                'http://localhost:8000',
            ],
        ]);

        RuntimeSecurity::apply();

        $this->assertSame('http://41.59.229.51:8092', config('services.jamii.cors_origins'));
        $this->assertSame(['http://41.59.229.51:8092'], config('cors.allowed_origins'));
    }

    public function test_request_host_lockdown_even_if_app_url_is_local(): void
    {
        config([
            'app.debug' => true,
            'app.url' => 'http://127.0.0.1:8001',
            'services.nida.driver' => 'fake',
            'services.nida.base_url' => '',
        ]);

        $this->assertFalse(RuntimeSecurity::shouldLockDown());
        $this->assertTrue(RuntimeSecurity::shouldLockDown('http://41.59.229.51:8010'));

        RuntimeSecurity::apply('http://41.59.229.51:8010');

        $this->assertFalse(config('app.debug'));
        $this->assertSame('http', config('services.nida.driver'));
    }

    public function test_cors_never_falls_back_to_wildcard(): void
    {
        config(['services.jamii.cors_origins' => '']);

        $this->assertSame('', JamiiCors::allowOrigin(Request::create('/', 'GET')));
    }
}
