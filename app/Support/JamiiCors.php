<?php

namespace App\Support;

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

        $shell = rtrim((string) config('services.jamii.shell_url'), '/');
        $allowedBases = array_unique(array_filter(array_merge(
            [$shell],
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
        return rtrim((string) config('services.jamii.shell_url'), '/').'/gateway';
    }

    public static function shellLoginUrl(array $query = []): string
    {
        $url = rtrim((string) config('services.jamii.shell_url'), '/').'/login';

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }
}
