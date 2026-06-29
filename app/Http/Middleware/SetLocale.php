<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
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
            Carbon::setLocale($locale === 'sw' ? 'sw_TZ' : 'en');
        }

        return $next($request);
    }
}
