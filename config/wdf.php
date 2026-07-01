<?php

return [
    'repayment_account' => [
        'bank_name' => env('WDF_REPAYMENT_BANK', 'CRDB Bank'),
        'account_number' => env('WDF_REPAYMENT_ACCOUNT', '0150001234567'),
        'account_name' => env('WDF_REPAYMENT_ACCOUNT_NAME', 'Women Development Fund'),
    ],
    'repayment_term_months' => (int) env('WDF_REPAYMENT_TERM_MONTHS', 12),
    'interest_rate' => 0.16,
];
