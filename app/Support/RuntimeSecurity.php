<?php

namespace App\Support;

/**
 * Locks down debug, fake NIDA, verbose logs, and localhost CORS
 * when the app is not on localhost.
 */
final class RuntimeSecurity
{
    public static function apply(?string $requestUrl = null): void
    {
        if (! self::shouldLockDown($requestUrl)) {
            return;
        }

        config([
            'app.debug' => false,
            'session.encrypt' => true,
            'logging.deprecations.trace' => false,
        ]);

        foreach (['single', 'daily', 'stderr', 'syslog', 'errorlog'] as $channel) {
            if (config("logging.channels.{$channel}.level") !== null) {
                config(["logging.channels.{$channel}.level" => 'warning']);
            }
        }

        if ((string) config('services.nida.driver') === 'fake') {
            config([
                'services.nida.driver' => 'http',
                'services.nida.enabled' => (string) config('services.nida.base_url') !== '',
            ]);
        }

        self::stripLocalCorsOrigins();

        if (self::isHttps() || ($requestUrl !== null && self::isHttps($requestUrl))) {
            config(['session.secure' => true]);
        }
    }

    public static function applyFromRequest(\Illuminate\Http\Request $request): void
    {
        $port = $request->getPort();
        $host = $request->getHost();
        $scheme = $request->getScheme() ?: 'http';
        $authority = $host;

        if ($port && ! in_array((int) $port, [80, 443], true)) {
            $authority .= ':'.$port;
        }

        self::apply($scheme.'://'.$authority);
    }

    public static function shouldLockDown(?string $requestUrl = null): bool
    {
        if (app()->environment('production')) {
            return true;
        }

        if (self::isExposedHost()) {
            return true;
        }

        return $requestUrl !== null && self::isExposedHost($requestUrl);
    }

    public static function isExposedHost(?string $url = null): bool
    {
        $url ??= (string) config('app.url');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            $host = strtolower((string) parse_url('http://'.$url, PHP_URL_HOST));
        }

        if ($host === '') {
            return false;
        }

        return ! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)
            && ! str_ends_with($host, '.localhost')
            && ! str_ends_with($host, '.test')
            && ! str_ends_with($host, '.local');
    }

    public static function isHttps(?string $url = null): bool
    {
        $url ??= (string) config('app.url');

        return str_starts_with(strtolower($url), 'https://');
    }

    /**
     * Live hosts must not allow localhost/dev origins even if .env still lists them.
     */
    public static function stripLocalCorsOrigins(): void
    {
        $cleaned = [];

        foreach (explode(',', (string) config('services.jamii.cors_origins', '')) as $origin) {
            $origin = rtrim(trim($origin), '/');
            if ($origin !== '' && self::isExposedHost($origin)) {
                $cleaned[] = $origin;
            }
        }

        $jumuishi = rtrim((string) config('jumuishi.url', ''), '/');
        if ($jumuishi !== '' && self::isExposedHost($jumuishi)) {
            array_unshift($cleaned, $jumuishi);
        }

        $cleaned = array_values(array_unique($cleaned));

        config([
            'services.jamii.cors_origins' => implode(',', $cleaned),
            'cors.allowed_origins' => $cleaned,
        ]);
    }
}
