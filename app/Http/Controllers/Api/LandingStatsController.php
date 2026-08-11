<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LandingStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingStatsController extends Controller
{
    public function __invoke(Request $request, LandingStatsService $landingStats): JsonResponse
    {
        $lang = $request->query('lang', app()->getLocale());
        if (in_array($lang, ['sw', 'en'], true)) {
            app()->setLocale($lang);
        }

        $stats = $landingStats->totals();

        return response()
            ->json([
                'stats' => $stats,
                'generated_at' => now()->toIso8601String(),
            ])
            ->header('Access-Control-Allow-Origin', $this->allowedOrigin($request))
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Accept, Content-Type');
    }

    public function options(Request $request): JsonResponse
    {
        return response()
            ->json(null, 204)
            ->header('Access-Control-Allow-Origin', $this->allowedOrigin($request))
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Accept, Content-Type')
            ->header('Access-Control-Max-Age', '86400');
    }

    protected function allowedOrigin(Request $request): string
    {
        $origin = (string) $request->headers->get('Origin', '');
        $allowed = array_filter(array_map(
            'trim',
            explode(',', (string) config('services.jamii.cors_origins', 'http://127.0.0.1:5173,http://127.0.0.1:5175,http://localhost:5173,http://localhost:5175'))
        ));

        if ($origin !== '' && in_array($origin, $allowed, true)) {
            return $origin;
        }

        return $allowed[0] ?? '*';
    }
}
