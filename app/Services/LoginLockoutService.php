<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class LoginLockoutService
{
    public function maxAttempts(): int
    {
        return max(1, (int) config('wdf.login_max_attempts', 3));
    }

    public function lockoutMinutes(): int
    {
        return max(1, (int) config('wdf.login_lockout_minutes', 5));
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->first();
    }

    /**
     * @return array{blocked: bool, message: ?string}
     */
    public function guard(User $user): array
    {
        if ($user->login_locked_permanently) {
            return [
                'blocked' => true,
                'message' => __('auth.locked_permanently'),
            ];
        }

        if ($this->isTemporarilyLocked($user)) {
            return [
                'blocked' => true,
                'message' => __('auth.locked_temporarily', [
                    'minutes' => $this->minutesRemaining($user),
                ]),
            ];
        }

        // Temp lock expired — clear the timer so the next attempt window can start cleanly.
        if ($user->login_locked_until !== null) {
            $user->forceFill(['login_locked_until' => null])->save();
        }

        return ['blocked' => false, 'message' => null];
    }

    /**
     * @return array{message: string, permanently_locked: bool, temporarily_locked: bool}
     */
    public function registerFailure(User $user): array
    {
        $attempts = (int) $user->failed_login_attempts + 1;
        $max = $this->maxAttempts();

        if ($attempts < $max) {
            $user->forceFill(['failed_login_attempts' => $attempts])->save();
            $remaining = $max - $attempts;

            return [
                'message' => trans_choice('auth.failed_with_remaining', $remaining, [
                    'remaining' => $remaining,
                    'minutes' => $this->lockoutMinutes(),
                ]),
                'permanently_locked' => false,
                'temporarily_locked' => false,
            ];
        }

        // Third failure in this window.
        $rounds = (int) $user->login_lockout_rounds;

        if ($rounds >= 1) {
            $user->forceFill([
                'failed_login_attempts' => 0,
                'login_locked_until' => null,
                'login_locked_permanently' => true,
            ])->save();

            return [
                'message' => __('auth.locked_permanently'),
                'permanently_locked' => true,
                'temporarily_locked' => false,
            ];
        }

        $minutes = $this->lockoutMinutes();
        $user->forceFill([
            'failed_login_attempts' => 0,
            'login_lockout_rounds' => $rounds + 1,
            'login_locked_until' => now()->addMinutes($minutes),
        ])->save();

        return [
            'message' => __('auth.locked_for_minutes', [
                'minutes' => $minutes,
            ]),
            'permanently_locked' => false,
            'temporarily_locked' => true,
        ];
    }

    public function clearOnSuccess(User $user): void
    {
        if (
            (int) $user->failed_login_attempts === 0
            && (int) $user->login_lockout_rounds === 0
            && $user->login_locked_until === null
            && ! $user->login_locked_permanently
        ) {
            return;
        }

        $user->forceFill([
            'failed_login_attempts' => 0,
            'login_lockout_rounds' => 0,
            'login_locked_until' => null,
            'login_locked_permanently' => false,
        ])->save();
    }

    public function unlock(User $user, bool $notify = true): void
    {
        $wasLocked = $user->login_locked_permanently
            || $user->login_locked_until !== null
            || (int) $user->failed_login_attempts > 0
            || (int) $user->login_lockout_rounds > 0;

        $user->forceFill([
            'failed_login_attempts' => 0,
            'login_lockout_rounds' => 0,
            'login_locked_until' => null,
            'login_locked_permanently' => false,
        ])->save();

        if ($notify && $wasLocked) {
            $user->notify(new \App\Notifications\AccountUnlockedNotification);
        }
    }

    public function isTemporarilyLocked(User $user): bool
    {
        if ($user->login_locked_permanently || ! $user->login_locked_until) {
            return false;
        }

        $until = $user->login_locked_until instanceof CarbonInterface
            ? $user->login_locked_until
            : Carbon::parse($user->login_locked_until);

        return $until->isFuture();
    }

    public function minutesRemaining(User $user): int
    {
        if (! $user->login_locked_until) {
            return 0;
        }

        $until = $user->login_locked_until instanceof CarbonInterface
            ? $user->login_locked_until
            : Carbon::parse($user->login_locked_until);

        $seconds = max(0, $until->getTimestamp() - now()->getTimestamp());

        return max(1, (int) ceil($seconds / 60));
    }
}
