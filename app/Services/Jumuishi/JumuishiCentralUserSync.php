<?php

namespace App\Services\Jumuishi;

use App\Models\User;
use App\Services\JumuishiUrl;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JumuishiCentralUserSync
{
    /**
     * Push a local WDF user to Jumuishi (module → central).
     * Preserves local roles/business data; central owns login identity.
     *
     * @return array{global_user_id: int, created: bool}
     */
    public function push(User $user, bool $syncPassword = true): array
    {
        if (! JumuishiUrl::enabled()) {
            throw new RuntimeException('Jumuishi integration is disabled.');
        }

        $passwordHash = $user->getRawOriginal('password');
        if (! is_string($passwordHash) || $passwordHash === '') {
            throw new RuntimeException("User {$user->email} has no password hash to sync.");
        }

        $url = JumuishiUrl::base().'/api/internal/users/sync';

        $response = Http::acceptJson()
            ->timeout(20)
            ->withHeaders([
                'X-Jumuishi-Module' => JumuishiUrl::modulePath(),
                'X-Jumuishi-Secret' => (string) config('jumuishi.api_secret'),
            ])
            ->post($url, [
                'local_user_id' => (string) $user->id,
                'first_name' => $user->first_name ?: 'User',
                'second_name' => $user->middle_name,
                'last_name' => $user->last_name ?: 'Account',
                'gender' => $this->mapGender($user->sex),
                'email' => $user->email,
                'password_hash' => $passwordHash,
                'status' => $user->is_active ? 'active' : 'disabled',
                'sync_password' => $syncPassword,
            ]);

        if (! $response->successful()) {
            $message = (string) ($response->json('message') ?? $response->body());
            $user->forceFill([
                'jumuishi_sync_status' => 'failed',
                'jumuishi_sync_error' => mb_substr($message, 0, 2000),
            ])->save();

            throw new RuntimeException(
                "Jumuishi sync failed for {$user->email}: HTTP {$response->status()} {$message}"
            );
        }

        $globalId = (int) ($response->json('data.global_user_id') ?? 0);
        if ($globalId < 1) {
            throw new RuntimeException("Jumuishi sync returned no global_user_id for {$user->email}.");
        }

        $user->forceFill([
            'global_user_id' => $globalId,
            'jumuishi_sync_status' => 'synced',
            'jumuishi_synced_at' => now(),
            'jumuishi_sync_error' => null,
        ])->save();

        return [
            'global_user_id' => $globalId,
            'created' => (bool) $response->json('data.created'),
        ];
    }

    protected function mapGender(?string $sex): ?string
    {
        return match (strtolower(trim((string) $sex))) {
            'female' => 'female',
            'male' => 'male',
            default => null,
        };
    }
}
