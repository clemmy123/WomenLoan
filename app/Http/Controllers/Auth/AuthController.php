<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Concerns\HasDisplayName;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
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

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (! Auth::user()->is_active) {
                Auth::logout();

                return back()->withErrors(['email' => __('auth.inactive')]);
            }

            activity('audit')
                ->causedBy(Auth::user())
                ->performedOn(Auth::user())
                ->event('login')
                ->log('User logged in');

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
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
