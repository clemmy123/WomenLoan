<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\CredentialAuthService;
use App\Services\Auth\JamiiSsoTicketService;
use App\Support\JamiiCors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JamiiSsoController extends Controller
{
    public function __construct(
        private JamiiSsoTicketService $tickets,
        private CredentialAuthService $credentials,
    ) {}

    public function consume(Request $request): RedirectResponse
    {
        $ticket = (string) $request->query('ticket', '');
        $next = (string) $request->query('next', '');
        $shellLogin = JamiiCors::shellLoginUrl(['error' => 'sso']);

        if ($ticket === '') {
            return redirect()->away($shellLogin);
        }

        $payload = $this->tickets->consume($ticket);
        if ($payload === null) {
            return redirect()->away($shellLogin);
        }

        $user = User::query()->find($payload['user_id']);
        if (! $user || ! $user->is_active) {
            return redirect()->away($shellLogin);
        }

        $this->credentials->establishSession($request, $user, $payload['remember']);

        if ($user->mustChangePassword()) {
            return redirect()
                ->route('profile.password.required')
                ->with('warning', __('auth.temporary_password_must_change', [
                    'minutes' => (int) config('wdf.temporary_password_minutes', 2),
                ]));
        }

        // Always return to shell gateway so the user sees assigned modules
        // (even a single one) and chooses to start — never auto-open dashboard.
        $destination = JamiiCors::isAllowedNext($next) ? $next : JamiiCors::defaultNext();

        return redirect()->away($destination);
    }
}
