<?php

namespace App\Services\Jumuishi;

use App\Services\JumuishiUrl;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JumuishiSsoClient
{
    /**
     * Exchange a one-time ticket with central Jumuishi.
     *
     * @return array{
     *     global_user_id: int,
     *     email: string,
     *     first_name?: ?string,
     *     second_name?: ?string,
     *     last_name?: ?string,
     *     full_name?: ?string,
     *     gender?: ?string,
     *     status?: ?string
     * }
     */
    public function exchangeTicket(string $ticket): array
    {
        if (! JumuishiUrl::enabled()) {
            throw new RuntimeException('Jumuishi integration is disabled.');
        }

        $url = JumuishiUrl::base().'/'.ltrim((string) config('jumuishi.sso_exchange_path'), '/');

        $response = Http::acceptJson()
            ->timeout(15)
            ->withHeaders([
                'X-Jumuishi-Module' => JumuishiUrl::modulePath(),
                'X-Jumuishi-Secret' => (string) config('jumuishi.api_secret'),
            ])
            ->post($url, ['ticket' => $ticket]);

        if (! $response->successful()) {
            $message = (string) ($response->json('message') ?? 'SSO ticket exchange failed.');

            throw new RuntimeException($message, $response->status());
        }

        $data = $response->json('data');
        if (! is_array($data) || empty($data['global_user_id']) || empty($data['email'])) {
            throw new RuntimeException('Invalid SSO exchange response.');
        }

        return $data;
    }
}
