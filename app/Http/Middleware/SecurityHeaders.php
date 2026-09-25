<?php

namespace App\Http\Middleware;

use App\Services\JumuishiUrl;
use App\Support\RuntimeSecurity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        RuntimeSecurity::applyFromRequest($request);

        /** @var Response $response */
        $response = $next($request);

        $jumuishi = rtrim(JumuishiUrl::base(), '/');
        $frameAncestors = "'self'";
        $formAction = "'self'";
        $connectSrc = "'self'";

        if ($jumuishi !== '') {
            $frameAncestors .= ' '.$jumuishi;
            $formAction .= ' '.$jumuishi;
            $connectSrc .= ' '.$jumuishi;
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors {$frameAncestors}",
            "form-action {$formAction}",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self'",
            "connect-src {$connectSrc}",
        ]);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->remove('X-Powered-By');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
