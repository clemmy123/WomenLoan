<?php

if (! function_exists('format_tzs')) {
    function format_tzs($amount): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0;

        return 'TZS ' . number_format($value, 0, '.', ',');
    }
}
