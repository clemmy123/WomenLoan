<?php

use App\Services\HashidService;

if (! function_exists('hashid_encode')) {
    function hashid_encode(int $id): string
    {
        return app(HashidService::class)->encode($id);
    }
}

if (! function_exists('hashid_decode')) {
    function hashid_decode(string $hash): ?int
    {
        return app(HashidService::class)->decode($hash);
    }
}
