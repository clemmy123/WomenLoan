<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale', 'en'));

        if (in_array($locale, ['en', 'sw'], true)) {
            app()->setLocale($locale);
        }

        // Do not call Carbon::setLocale() here. On slow disks (OneDrive Desktop)
        // loading Carbon language packs can exceed max_execution_time during logout
        // and other short requests (fatal error in Carbon\Traits\Date).

        return $next($request);
    }
}
