<?php

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
