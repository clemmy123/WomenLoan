<?php

namespace App\Support;

class SecureFileUrl
{
    public static function encodePath(string $path): string
    {
        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }

    public static function forPath(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return route('secure-files.show', ['path' => self::encodePath($path)]);
    }
}
