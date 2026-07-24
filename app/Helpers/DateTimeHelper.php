<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('app_timezone')) {
    function app_timezone(): string
    {
        return (string) config('app.timezone', 'Africa/Dar_es_Salaam');
    }
}

if (! function_exists('format_app_datetime')) {
    /**
     * App-local datetime, e.g. "24 Jul 2026 2:46 PM" (or with seconds).
     */
    function format_app_datetime(mixed $value = null, bool $withSeconds = false): string
    {
        if ($value === null || $value === '') {
            $value = now();
        }

        $dt = $value instanceof CarbonInterface
            ? $value->copy()->timezone(app_timezone())
            : Carbon::parse($value)->timezone(app_timezone());

        $format = $withSeconds ? 'd M Y g:i:s A' : 'd M Y g:i A';

        return $dt->translatedFormat($format);
    }
}
