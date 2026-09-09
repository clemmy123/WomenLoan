<?php

namespace Tests\Unit;

use App\Support\SafeVite;
use Illuminate\Http\Exceptions\HttpResponseException;
use Tests\TestCase;

class SafeViteTest extends TestCase
{
    public function test_missing_manifest_shows_admin_message_instead_of_throwing(): void
    {
        $vite = new SafeVite;
        $vite->useHotFile(storage_path('framework/vite.hot-missing'));

        try {
            $vite(['resources/css/app.css'], 'build-does-not-exist');
            $this->fail('Expected a friendly error response.');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
            $this->assertSame(500, $response->getStatusCode());
            $this->assertStringContainsString(__('messages.consult_admin'), $response->getContent());
        }
    }
}
