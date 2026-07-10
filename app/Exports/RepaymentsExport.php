<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class RepaymentsExport implements FromArray, WithTitle
{
    public function __construct(
        private array $summary,
        private Collection $rows,
        private array $filters,
    ) {}

    public function array(): array
    {
        $lines = [
            [__('repayments.payments_title')],
            [__('repayments.filter_status'), $this->filters['status'] ?? 'all'],
            [__('common.search'), $this->filters['search'] ?? ''],
            [__('repayments.summary_loans'), $this->summary['count']],
            [__('repayments.disbursed_col'), $this->summary['total_disbursed']],
            [__('repayments.amount_paid_col'), $this->summary['total_paid']],
            [__('repayments.outstanding'), $this->summary['total_outstanding']],
            [],
            [
                __('dashboard.track_id'),
                __('loans.applicant_name'),
                __('repayments.disbursed_col'),
                __('repayments.amount_paid_col'),
                __('repayments.outstanding'),
                __('dashboard.status'),
                __('repayments.start_date'),
            ],
        ];

        foreach ($this->rows as $row) {
            $lines[] = [
                $row['track_id'],
                $row['name'],
                $row['disbursed'],
                $row['paid'],
                $row['outstanding'],
                $row['status'],
                $row['start_date'],
            ];
        }

        return $lines;
    }

    public function title(): string
    {
        return __('repayments.payments_title');
    }
}
