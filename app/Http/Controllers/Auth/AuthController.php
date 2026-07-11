<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Concerns\HasDisplayName;
use App\Models\User;
use App\Services\LoginLockoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private LoginLockoutService $lockout) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = $credentials['email'];
        $user = $this->lockout->findByEmail($email);

        if ($user) {
            $guard = $this->lockout->guard($user);
            if ($guard['blocked']) {
                return back()
                    ->withErrors(['email' => $guard['message']])
                    ->onlyInput('email');
            }

            if (! $user->is_active) {
                return back()
                    ->withErrors(['email' => __('auth.inactive')])
                    ->onlyInput('email');
            }
        }

        if ($user && Hash::check($credentials['password'], $user->password)) {
            if ($user->mustChangePassword() && $user->temporaryPasswordExpired()) {
                return back()
                    ->withErrors(['email' => __('auth.temporary_password_expired')])
                    ->onlyInput('email');
            }

            Auth::login($user, $request->boolean('remember'));
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

            if ($user->mustChangePassword()) {
                return redirect()
                    ->route('profile.password.required')
                    ->with('warning', __('auth.temporary_password_must_change', [
                        'minutes' => (int) config('wdf.temporary_password_minutes', 2),
                    ]));
            }

            return redirect()->intended(route('dashboard'));
        }

        if ($user) {
            $result = $this->lockout->registerFailure($user);

            return back()
                ->withErrors(['email' => $result['message']])
                ->onlyInput('email');
        }

        return back()
            ->withErrors(['email' => __('auth.failed')])
            ->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => HasDisplayName::buildFullName(
                $validated['first_name'],
                $validated['middle_name'] ?? null,
                $validated['last_name']
            ),
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
        ]);

        $user->assignRole('applicant');

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', __('messages.register_success'));
    }

    public function logout(Request $request)
    {
        if (Auth::user()) {
            activity('audit')
                ->causedBy(Auth::user())
                ->performedOn(Auth::user())
                ->event('logout')
                ->log('User logged out');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
