<?php

return [
    'repayment_account' => [
        'bank_name' => env('WDF_REPAYMENT_BANK', 'CRDB Bank'),
        'account_number' => env('WDF_REPAYMENT_ACCOUNT', '01J1027640100'),
        'account_name' => env('WDF_REPAYMENT_ACCOUNT_NAME', 'Women Development Funds'),
    ],
    'payment_methods' => [
        'Bank Transfer',
        'M-Pesa',
        'Tigo Pesa',
        'Airtel Money',
        'HaloPesa',
        'CRDB SimBanking',
        'NMB Mobile',
        'Cash Deposit',
    ],
    'repayment_term_months' => (int) env('WDF_REPAYMENT_TERM_MONTHS', 12),
    'grace_period_months' => (int) env('WDF_GRACE_PERIOD_MONTHS', 3),
    'interest_rate' => 0.16,
];
