<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Jumuishi\JumuishiSsoClient;
use App\Services\Jumuishi\JumuishiUserProvisioner;
use App\Services\JumuishiUrl;
use App\Support\AccessibleHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class JumuishiSsoConsumeController extends Controller
{
    public function __construct(
        private JumuishiSsoClient $sso,
        private JumuishiUserProvisioner $provisioner,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $shellLogin = JumuishiUrl::base().'/login';

        if (! JumuishiUrl::enabled()) {
            return redirect()->route('login');
        }

        $ticket = (string) $request->query('ticket', '');
        $returnTo = JumuishiUrl::safeReturnTo((string) $request->query('return_to', '')) ?? '/';

        if ($ticket === '' || strlen($ticket) !== 64) {
            return redirect()->away($shellLogin);
        }

        try {
            $identity = $this->sso->exchangeTicket($ticket);
            $user = $this->provisioner->upsert([
                'global_user_id' => $identity['global_user_id'],
                'email' => $identity['email'],
                'first_name' => $identity['first_name'] ?? null,
                'second_name' => $identity['second_name'] ?? null,
                'last_name' => $identity['last_name'] ?? null,
                'gender' => $identity['gender'] ?? null,
                'status' => $identity['status'] ?? 'active',
            ]);

            if (! $user->is_active) {
                return redirect()->away($shellLogin);
            }

            Auth::login($user, false);
            $request->session()->regenerate();

            activity('audit')
                ->causedBy($user)
                ->performedOn($user)
                ->event('login')
                ->log('User logged in via Jumuishi SSO');

            $destination = $returnTo === '/'
                ? AccessibleHome::url($user)
                : $returnTo;

            return redirect()->to($destination);
        } catch (Throwable $e) {
            report($e instanceof RuntimeException ? $e : $e);

            return redirect()->away($shellLogin);
        }
    }
}
