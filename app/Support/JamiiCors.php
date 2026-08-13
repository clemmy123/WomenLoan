<?php

namespace App\Support;

use App\Services\JumuishiUrl;
use Illuminate\Http\Request;

class JamiiCors
{
    /**
     * @return list<string>
     */
    public static function allowedOrigins(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('services.jamii.cors_origins', ''))
        )));
    }

    public static function allowOrigin(Request $request): string
    {
        $origin = (string) $request->headers->get('Origin', '');
        $allowed = self::allowedOrigins();

        if ($origin !== '' && in_array($origin, $allowed, true)) {
            return $origin;
        }

        return $allowed[0] ?? '*';
    }

    public static function isAllowedNext(?string $next): bool
    {
        if (! is_string($next) || $next === '') {
            return false;
        }

        $allowedBases = array_unique(array_filter(array_merge(
            [self::centralBase()],
            self::allowedOrigins()
        )));

        foreach ($allowedBases as $base) {
            $base = rtrim($base, '/');
            if ($base !== '' && (str_starts_with($next, $base.'/') || $next === $base)) {
                return true;
            }
        }

        return false;
    }

    public static function defaultNext(): string
    {
        return self::centralBase();
    }

    public static function shellLoginUrl(array $query = []): string
    {
        $url = self::centralBase().'/login';

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }

    private static function centralBase(): string
    {
        if (JumuishiUrl::enabled() && JumuishiUrl::base() !== '') {
            return JumuishiUrl::base();
        }

        return rtrim((string) config('services.jamii.shell_url'), '/');
    }
}
