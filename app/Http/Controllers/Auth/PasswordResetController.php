<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\JumuishiUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showLinkRequestForm(): RedirectResponse|View
    {
        if (JumuishiUrl::enabled()) {
            return redirect()->away(JumuishiUrl::forgotPassword());
        }

        return view('auth.forgot-password');
    }

    /**
     * Public self-service reset is disabled. Guests always receive
     * "user not found" — password resets for other accounts are done
     * by staff with "manage users" / "reset user password".
     * When Jumuishi is enabled, identity reset lives on the central platform.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        if (JumuishiUrl::enabled()) {
            return redirect()->away(JumuishiUrl::forgotPassword());
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('passwords.user')]);
    }

    public function showResetForm(Request $request, string $token): RedirectResponse
    {
        if (JumuishiUrl::enabled()) {
            return redirect()->away(JumuishiUrl::forgotPassword());
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => __('passwords.user')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        if (JumuishiUrl::enabled()) {
            return redirect()->away(JumuishiUrl::forgotPassword());
        }

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed'],
        ]);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('passwords.user')]);
    }
}
