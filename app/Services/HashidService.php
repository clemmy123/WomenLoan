<?php

namespace App\Services;

use Hashids\Hashids;

class HashidService
{
    private Hashids $hashids;

    public function __construct()
    {
        $this->hashids = new Hashids(
            config('hashids.salt'),
            config('hashids.length'),
            config('hashids.alphabet'),
        );
    }

    public function encode(int $id): string
    {
        return $this->hashids->encode($id);
    }

    public function decode(string $hash): ?int
    {
        $decoded = $this->hashids->decode($hash);

        return isset($decoded[0]) ? (int) $decoded[0] : null;
    }
}
