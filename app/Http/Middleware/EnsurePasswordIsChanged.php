<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->mustChangePassword()) {
            return $next($request);
        }

        if ($user->temporaryPasswordExpired()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('auth.temporary_password_expired')]);
        }

        if ($request->routeIs('profile.password.required', 'profile.password.required.update', 'logout')) {
            return $next($request);
        }

        return redirect()
            ->route('profile.password.required')
            ->with('warning', __('auth.temporary_password_must_change', [
                'minutes' => (int) config('wdf.temporary_password_minutes', 2),
            ]));
    }
}
