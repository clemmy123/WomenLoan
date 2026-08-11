<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\CredentialAuthService;
use App\Services\Auth\JamiiSsoTicketService;
use App\Support\JamiiCors;
use App\Support\JamiiModuleAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JamiiAuthController extends Controller
{
    public function __construct(
        private CredentialAuthService $credentials,
        private JamiiSsoTicketService $tickets,
    ) {}

    public function options(Request $request): JsonResponse
    {
        return $this->corsJson($request, null, 204);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $result = $this->credentials->attempt($validated['email'], $validated['password']);

        if (! $result['ok']) {
            return $this->corsJson($request, [
                'message' => $result['message'],
            ], 401);
        }

        $issued = $this->tickets->issue(
            $result['user']->id,
            (bool) ($validated['remember'] ?? false),
        );

        $user = $result['user'];

        return $this->corsJson($request, [
            'ticket' => $issued['ticket'],
            'expires_in' => $issued['expires_in'],
            'must_change_password' => $user->mustChangePassword(),
            'audience' => $user->isApplicant() ? 'applicant' : 'staff',
            'modules' => JamiiModuleAccess::idsFor($user),
        ]);
    }

    protected function corsJson(Request $request, mixed $data, int $status = 200): JsonResponse
    {
        $response = response()->json($data, $status);

        return $response
            ->header('Access-Control-Allow-Origin', JamiiCors::allowOrigin($request))
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Accept, Content-Type')
            ->header('Access-Control-Max-Age', '86400');
    }
}
