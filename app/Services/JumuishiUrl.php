<?php

namespace App\Services;

/**
 * Build safe Jumuishi URLs for SSO and identity flows.
 */
class JumuishiUrl
{
    public static function enabled(): bool
    {
        return (bool) config('jumuishi.enabled');
    }

    public static function base(): string
    {
        return rtrim((string) config('jumuishi.url'), '/');
    }

    public static function modulePath(): string
    {
        return trim((string) config('jumuishi.module_path'), '/');
    }

    /**
     * Central SSO launch for this module.
     * $returnTo must be a relative path starting with one `/`.
     */
    public static function ssoStart(?string $returnTo = null): string
    {
        $url = self::base().'/'.trim((string) config('jumuishi.sso_start_path'), '/').'/'.self::modulePath();

        $safe = self::safeReturnTo($returnTo);
        if ($safe !== null) {
            $url .= '?'.http_build_query(['return_to' => $safe]);
        }

        return $url;
    }

    public static function forgotPassword(): string
    {
        return self::base().'/'.ltrim((string) config('jumuishi.forgot_password_path'), '/');
    }

    public static function passwordReset(): string
    {
        return self::base().'/'.ltrim((string) config('jumuishi.password_path'), '/');
    }

    public static function centralLogout(): string
    {
        return self::base().'/'.ltrim((string) config('jumuishi.central_logout_path'), '/');
    }

    /**
     * Protected module deep-link via Jumuishi (for business emails).
     */
    public static function module(string $relativePath): string
    {
        $safe = self::safeReturnTo($relativePath) ?? '/';

        return self::ssoStart($safe);
    }

    public static function safeReturnTo(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        if (str_contains($path, '://')) {
            return null;
        }

        return $path;
    }

    /**
     * Relative return_to from the current request (path + query).
     */
    public static function returnToFromRequest(\Illuminate\Http\Request $request): string
    {
        $path = $request->getPathInfo() ?: '/';
        $query = $request->getQueryString();

        $candidate = $query ? $path.'?'.$query : $path;

        return self::safeReturnTo($candidate) ?? '/';
    }
}
