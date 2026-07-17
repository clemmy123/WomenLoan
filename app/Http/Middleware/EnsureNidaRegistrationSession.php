<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNidaRegistrationSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('nida_registration_allowed')) {
            abort(403);
        }

        return $next($request);
    }
}
