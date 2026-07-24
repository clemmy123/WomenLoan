<?php

require_once __DIR__.'/DateTimeHelper.php';

if (! function_exists('format_tzs')) {
    function format_tzs($amount): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0;

        return 'TZS '.number_format($value, 0, '.', ',');
    }
}

if (! function_exists('format_amount_input')) {
    function format_amount_input(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', (string) $amount) ?? '';

        if ($digits === '') {
            return '';
        }

        return number_format((float) $digits, 0, '', ',');
    }
}

if (! function_exists('format_payment_datetime')) {
    /**
     * Full payment timestamp: day, month, year, hour, minute, second, AM/PM.
     */
    function format_payment_datetime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return format_app_datetime($value, withSeconds: true);
    }
}
