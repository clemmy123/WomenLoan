<?php

namespace App\Services\Jumuishi;

use App\Models\Concerns\HasDisplayName;
use App\Models\JumuishiProcessedEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class JumuishiUserProvisioner
{
    /**
     * Upsert a local user from central identity fields.
     *
     * @param  array{
     *     global_user_id: int|string,
     *     email: string,
     *     first_name?: ?string,
     *     second_name?: ?string,
     *     last_name?: ?string,
     *     gender?: ?string,
     *     password_hash?: ?string,
     *     status?: ?string,
     *     token_version?: int|null
     * }  $payload
     */
    public function upsert(array $payload): User
    {
        $globalId = (int) $payload['global_user_id'];
        $email = $this->normalizeEmail((string) $payload['email']);

        if ($email === '' || $globalId < 1) {
            throw new RuntimeException('Invalid Jumuishi user payload.');
        }

        return DB::transaction(function () use ($payload, $globalId, $email) {
            $user = User::query()
                ->where('global_user_id', $globalId)
                ->lockForUpdate()
                ->first();

            if ($user === null) {
                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->lockForUpdate()
                    ->first();
            }

            $first = trim((string) ($payload['first_name'] ?? ''));
            $middle = trim((string) ($payload['second_name'] ?? ''));
            $last = trim((string) ($payload['last_name'] ?? ''));
            $status = strtolower((string) ($payload['status'] ?? 'active'));
            $isActive = in_array($status, ['active', ''], true);

            $attributes = [
                'global_user_id' => $globalId,
                'email' => $email,
                'first_name' => $first !== '' ? $first : ($user?->first_name),
                'middle_name' => $middle !== '' ? $middle : ($user?->middle_name),
                'last_name' => $last !== '' ? $last : ($user?->last_name),
                'is_active' => $isActive,
                'jumuishi_sync_status' => 'synced',
                'jumuishi_synced_at' => now(),
                'jumuishi_sync_error' => null,
            ];

            if ($first !== '' || $middle !== '' || $last !== '') {
                $attributes['name'] = HasDisplayName::buildFullName(
                    $attributes['first_name'] ?? 'User',
                    $attributes['middle_name'] ?? null,
                    $attributes['last_name'] ?? ''
                );
            } elseif ($user === null) {
                $attributes['name'] = Str::before($email, '@') ?: 'User';
                $attributes['first_name'] = $attributes['first_name'] ?: $attributes['name'];
                $attributes['last_name'] = $attributes['last_name'] ?: 'Account';
            }

            $sex = $this->mapSex($payload['gender'] ?? null);
            if ($sex !== null) {
                $attributes['sex'] = $sex;
            }

            if (array_key_exists('token_version', $payload) && $payload['token_version'] !== null) {
                $attributes['token_version'] = (int) $payload['token_version'];
            }

            if ($user === null) {
                $user = new User;
                $attributes['password'] = $this->resolvePasswordHash($payload['password_hash'] ?? null);
                $user->forceFill($attributes)->save();

                if (! $user->roles()->exists()) {
                    $user->assignRole('applicant');
                }

                return $user->fresh();
            }

            if (! empty($payload['password_hash'])) {
                $this->setPasswordHash($user, (string) $payload['password_hash']);
            }

            if (! $isActive) {
                $attributes['deactivated_at'] = $user->deactivated_at ?? now();
                $attributes['deactivation_reason'] = $user->deactivation_reason ?: 'Disabled by Jumuishi';
            } else {
                $attributes['deactivated_at'] = null;
                $attributes['deactivation_reason'] = null;
                $attributes['deactivated_by'] = null;
            }

            $user->forceFill($attributes)->save();

            return $user->fresh();
        });
    }

    public function applyLifecycleEvent(array $payload): ?User
    {
        $eventUuid = (string) ($payload['event_uuid'] ?? '');
        $eventType = (string) ($payload['event_type'] ?? '');

        if ($eventUuid === '' || $eventType === '') {
            throw new RuntimeException('Missing event_uuid or event_type.');
        }

        return DB::transaction(function () use ($payload, $eventUuid, $eventType) {
            $existing = JumuishiProcessedEvent::query()
                ->whereKey($eventUuid)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing->local_user_id
                    ? User::query()->find($existing->local_user_id)
                    : null;
            }

            $user = $this->findByGlobalOrEmail(
                $payload['global_user_id'] ?? null,
                $payload['email'] ?? null
            );

            if ($eventType === 'password.changed') {
                if ($user && ! empty($payload['password_hash'])) {
                    $this->setPasswordHash($user, (string) $payload['password_hash']);
                    $user->forceFill([
                        'must_change_password' => false,
                        'temporary_password_expires_at' => null,
                        'jumuishi_sync_status' => 'synced',
                        'jumuishi_synced_at' => now(),
                        'jumuishi_sync_error' => null,
                    ])->save();
                }
            } elseif ($eventType === 'user.disabled' || ($eventType === 'module.access.revoked')) {
                if ($user) {
                    $user->forceFill([
                        'is_active' => false,
                        'deactivated_at' => now(),
                        'deactivation_reason' => $eventType === 'module.access.revoked'
                            ? 'Module access revoked by Jumuishi'
                            : 'Disabled by Jumuishi',
                        'jumuishi_sync_status' => 'synced',
                        'jumuishi_synced_at' => now(),
                        'jumuishi_sync_error' => null,
                    ])->save();
                }
            } elseif ($eventType === 'user.enabled') {
                if ($user) {
                    $user->forceFill([
                        'is_active' => true,
                        'deactivated_at' => null,
                        'deactivation_reason' => null,
                        'deactivated_by' => null,
                        'jumuishi_sync_status' => 'synced',
                        'jumuishi_synced_at' => now(),
                        'jumuishi_sync_error' => null,
                    ])->save();
                }
            } elseif ($eventType === 'user.created') {
                $user = $this->upsert($payload);
            } else {
                // Unknown event: acknowledge without failing delivery retries.
            }

            JumuishiProcessedEvent::query()->create([
                'event_uuid' => $eventUuid,
                'event_type' => $eventType,
                'local_user_id' => $user?->id,
            ]);

            return $user?->fresh();
        });
    }

    public function markFailed(User $user, string $error): void
    {
        $user->forceFill([
            'jumuishi_sync_status' => 'failed',
            'jumuishi_sync_error' => Str::limit($error, 2000),
        ])->save();
    }

    protected function findByGlobalOrEmail(mixed $globalId, mixed $email): ?User
    {
        if ($globalId) {
            $byGlobal = User::query()->where('global_user_id', (int) $globalId)->first();
            if ($byGlobal) {
                return $byGlobal;
            }
        }

        $normalized = $this->normalizeEmail((string) $email);
        if ($normalized === '') {
            return null;
        }

        return User::query()->whereRaw('LOWER(email) = ?', [$normalized])->first();
    }

    protected function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    protected function mapSex(mixed $gender): ?string
    {
        $value = Str::lower(trim((string) $gender));

        return match ($value) {
            'female' => 'Female',
            'male' => 'Male',
            default => null,
        };
    }

    protected function resolvePasswordHash(?string $hash): string
    {
        if (is_string($hash) && $hash !== '') {
            return $hash;
        }

        return Hash::make(Str::password(32));
    }

    protected function setPasswordHash(User $user, string $hash): void
    {
        // Bypass "hashed" cast so a central bcrypt/argon hash is stored as-is.
        DB::table('users')->where('id', $user->id)->update([
            'password' => $hash,
            'updated_at' => now(),
        ]);
        $user->password = $hash;
    }
}
