<?php

namespace App\Support;

class IdentityNormalizer
{
    public static function normalizeNin(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function formatNin(mixed $value): string
    {
        $digits = self::normalizeNin($value);

        if ($digits === '') {
            return '';
        }

        $parts = [
            substr($digits, 0, 8),
            substr($digits, 8, 5),
            substr($digits, 13, 5),
            substr($digits, 18, 2),
        ];

        $formatted = [];
        $segments = [8, 5, 5, 2];
        $offset = 0;

        foreach ($segments as $length) {
            $chunk = substr($digits, $offset, $length);

            if ($chunk === '') {
                break;
            }

            $formatted[] = $chunk;
            $offset += $length;
        }

        return implode('-', $formatted);
    }

    public static function normalizePhone(mixed $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = '255'.substr($digits, 1);
        }

        if (strlen($digits) === 9 && preg_match('/^[67]/', $digits)) {
            $digits = '255'.$digits;
        }

        return $digits;
    }

    public static function phoneLocalPart(mixed $value): string
    {
        $normalized = self::normalizePhone($value);

        if (str_starts_with($normalized, '255') && strlen($normalized) >= 12) {
            return substr($normalized, 3, 9);
        }

        return '';
    }

    public static function formatPhone(mixed $value): string
    {
        $local = self::phoneLocalPart($value);

        return $local !== '' ? '+255 '.$local : '';
    }

    public static function normalizeEmail(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    public static function normalizeAmount(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }
}
