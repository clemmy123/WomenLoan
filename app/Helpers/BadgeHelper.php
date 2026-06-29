<?php

if (! function_exists('loan_status_badge_variant')) {
    function loan_status_badge_variant(?string $status): string
    {
        return match ($status) {
            'pending' => 'secondary',
            'received' => 'info',
            'in_review' => 'primary',
            'awaiting_applicant' => 'warning',
            'declined_by_applicant', 'rejected' => 'danger',
            'approved', 'ready_for_disbursement', 'disbursed' => 'success',
            default => 'secondary',
        };
    }
}

if (! function_exists('active_status_badge_variant')) {
    function active_status_badge_variant(bool $isActive): string
    {
        return $isActive ? 'success' : 'danger';
    }
}
