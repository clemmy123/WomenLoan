<?php

namespace App\Http\Controllers\Api\Jumuishi;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $database = 'connected';
        } catch (Throwable) {
            return response()->json([
                'status' => 'error',
                'database' => 'disconnected',
                'message' => 'Database connection failed.',
            ], 503);
        }

        return response()->json([
            'status' => 'success',
            'database' => $database,
            'cache' => config('cache.default'),
            'version' => config('app.version', '1.0.0'),
        ]);
    }
}
