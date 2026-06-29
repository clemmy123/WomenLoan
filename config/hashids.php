<?php

return [
    'salt' => env('HASHIDS_SALT', env('APP_KEY', 'women-loan-fund')),
    'length' => (int) env('HASHIDS_LENGTH', 8),
    'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
];
