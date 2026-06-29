<?php

namespace App\Models\Concerns;

use App\Services\HashidService;

trait HasHashid
{
    public function getHashidAttribute(): string
    {
        return app(HashidService::class)->encode((int) $this->getKey());
    }

    public function getRouteKey(): mixed
    {
        return $this->hashid;
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        $id = app(HashidService::class)->decode((string) $value);

        if ($id === null) {
            return null;
        }

        return $this->resolveRouteBindingQuery($this, $id, $field)->first();
    }

    public static function findByHashid(string $hash): ?static
    {
        $id = app(HashidService::class)->decode($hash);

        return $id ? static::query()->find($id) : null;
    }

    public static function findByHashidOrFail(string $hash): static
    {
        return static::findByHashid($hash) ?? abort(404);
    }
}
