<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LandingStatsService;
use App\Support\JamiiCors;
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
        return JamiiCors::allowOrigin($request);
    }
}
