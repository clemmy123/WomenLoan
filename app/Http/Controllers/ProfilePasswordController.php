<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ProfilePasswordController extends Controller
{
    public function edit()
    {
        return view('profile.password');
    }

    public function editRequired()
    {
        $user = auth()->user();

        abort_unless($user?->mustChangePassword(), 403);

        return view('profile.password-required', [
            'expiresAt' => $user->temporary_password_expires_at,
            'minutes' => (int) config('wdf.temporary_password_minutes', 2),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->update([
            'password' => $validated['password'],
        ]);
        $user->clearTemporaryPasswordRequirement();

        return redirect()
            ->route('profile.password.edit')
            ->with('success', __('messages.password_changed'));
    }

    public function updateRequired(Request $request)
    {
        $user = $request->user();

        abort_unless($user?->mustChangePassword(), 403);

        if ($user->temporaryPasswordExpired()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('auth.temporary_password_expired')]);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);
        $user->clearTemporaryPasswordRequirement();

        return redirect()
            ->route('dashboard')
            ->with('success', __('messages.password_changed'));
    }
}
