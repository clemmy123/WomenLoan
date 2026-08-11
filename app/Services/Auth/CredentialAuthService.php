<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\LoginLockoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CredentialAuthService
{
    public function __construct(private LoginLockoutService $lockout) {}

    /**
     * Validate credentials without establishing a session.
     *
     * @return array{ok: true, user: User}|array{ok: false, message: string}
     */
    public function attempt(string $email, string $password): array
    {
        $user = $this->lockout->findByEmail($email);

        if ($user) {
            $guard = $this->lockout->guard($user);
            if ($guard['blocked']) {
                return ['ok' => false, 'message' => (string) $guard['message']];
            }

            if (! $user->is_active) {
                return ['ok' => false, 'message' => __('auth.inactive')];
            }
        }

        if ($user && Hash::check($password, $user->password)) {
            if ($user->mustChangePassword() && $user->temporaryPasswordExpired()) {
                return ['ok' => false, 'message' => __('auth.temporary_password_expired')];
            }

            return ['ok' => true, 'user' => $user];
        }

        if ($user) {
            $result = $this->lockout->registerFailure($user);

            return ['ok' => false, 'message' => $result['message']];
        }

        return ['ok' => false, 'message' => __('auth.failed')];
    }

    public function establishSession(Request $request, User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->lockout->clearOnSuccess($user);

        if ($user->mustChangePassword()) {
            $user->startTemporaryPasswordWindow();
        }

        activity('audit')
            ->causedBy($user)
            ->performedOn($user)
            ->event('login')
            ->log('User logged in');
    }
}
