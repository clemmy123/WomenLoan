<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Concerns\HasDisplayName;
use App\Models\User;
use App\Services\LoginLockoutService;
use App\Support\AccessibleHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

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

            return $this->redirectAfterLogin($request, $user)
                ->with('success', __('audit.events.login'));
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
        session(['nida_registration_allowed' => true]);

        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $userPayload = [
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'name' => HasDisplayName::buildFullName(
                $validated['first_name'],
                $validated['middle_name'] ?? null,
                $validated['last_name']
            ),
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
        ];

        if ((bool) config('services.nida.enabled') && ! empty($validated['nin'])) {
            $nida = app(\App\Services\Nida\NidaService::class);
            $identity = $nida->pullVerified($validated['nin']);

            $userPayload['nin'] = $validated['nin'];
            $userPayload['dob'] = $validated['dob'] ?? null;
            $userPayload['sex'] = $validated['sex'] ?? 'Female';
            $userPayload['nationality'] = $validated['nationality'] ?? 'Tanzanian';
            $userPayload['nida_verified_at'] = now();

            if ($identity?->photoBase64) {
                $binary = base64_decode($identity->photoBase64, true);
                if ($binary !== false) {
                    $isSvg = str_starts_with(ltrim($binary), '<svg') || str_starts_with($identity->photoBase64, 'PHN2Zy');
                    $path = 'applicants/photos/'.\Illuminate\Support\Str::uuid().'.'.($isSvg ? 'svg' : 'jpg');
                    \Illuminate\Support\Facades\Storage::disk('public')->put($path, $binary);
                    $userPayload['nida_photo_path'] = $path;
                }
                $nida->forgetVerified($validated['nin']);
            }
        }

        $user = User::create($userPayload);

        $user->assignRole('applicant');

        Auth::login($user);

        return redirect()->to(AccessibleHome::url($user))
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

    /**
     * Prefer the intended URL after login, but never land on a 403 page.
     */
    protected function redirectAfterLogin(Request $request, User $user): RedirectResponse
    {
        $default = AccessibleHome::url($user);
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '' || $intended === $default) {
            return redirect()->to($default);
        }

        if (! $this->userCanAccessIntendedUrl($user, $intended)) {
            return redirect()->to($default);
        }

        return redirect()->to($intended);
    }

    protected function userCanAccessIntendedUrl(User $user, string $intended): bool
    {
        $path = parse_url($intended, PHP_URL_PATH);

        if (! is_string($path) || $path === '' || str_starts_with($path, '//')) {
            return false;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if (str_starts_with($intended, 'http://') || str_starts_with($intended, 'https://')) {
            if ($appUrl !== '' && ! str_starts_with($intended, $appUrl)) {
                return false;
            }
        }

        try {
            $route = Route::getRoutes()->match(Request::create($intended, 'GET'));
        } catch (\Throwable) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if (str_starts_with($middleware, 'can:')) {
                $abilities = explode(',', substr($middleware, 4));

                foreach ($abilities as $ability) {
                    $ability = trim($ability);
                    if ($ability !== '' && ! $user->can($ability)) {
                        return false;
                    }
                }
            }

            if (str_starts_with($middleware, 'role:')) {
                $roles = array_map('trim', explode('|', substr($middleware, 5)));
                if ($roles !== [] && ! $user->hasAnyRole($roles)) {
                    return false;
                }
            }

            if (str_starts_with($middleware, 'permission:')) {
                $permissions = array_map('trim', explode('|', substr($middleware, 11)));
                if ($permissions !== [] && ! $user->canAny($permissions)) {
                    return false;
                }
            }
        }

        return true;
    }
}
