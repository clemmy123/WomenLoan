<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyJumuishiPlatformSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('jumuishi.platform_secret', '');
        $provided = (string) $request->header('X-Jumuishi-Platform-Secret', '');

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid platform secret.',
            ], 401);
        }

        return $next($request);
    }
}
