<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class JamiiSsoTicketService
{
    public function ttlSeconds(): int
    {
        return max(15, (int) config('services.jamii.sso_ticket_ttl', 60));
    }

    /**
     * @return array{ticket: string, expires_in: int}
     */
    public function issue(int $userId, bool $remember = false): array
    {
        $ticket = Str::random(64);
        $ttl = $this->ttlSeconds();

        Cache::put($this->cacheKey($ticket), [
            'user_id' => $userId,
            'remember' => $remember,
        ], $ttl);

        return [
            'ticket' => $ticket,
            'expires_in' => $ttl,
        ];
    }

    /**
     * @return array{user_id: int, remember: bool}|null
     */
    public function consume(string $ticket): ?array
    {
        $key = $this->cacheKey($ticket);
        $payload = Cache::pull($key);

        if (! is_array($payload) || ! isset($payload['user_id'])) {
            return null;
        }

        return [
            'user_id' => (int) $payload['user_id'],
            'remember' => (bool) ($payload['remember'] ?? false),
        ];
    }

    protected function cacheKey(string $ticket): string
    {
        return 'jamii.sso.ticket.'.$ticket;
    }
}
