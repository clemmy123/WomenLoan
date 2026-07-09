<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ApplicationReportsExport implements FromArray, WithTitle
{
    public function __construct(
        private Collection $rows,
        private array $filters,
    ) {}

    public function array(): array
    {
        $statusLabel = $this->filters['status']
            ? loan_status_label($this->filters['status'])
            : __('application_reports.all_statuses');

        $lines = [
            [__('application_reports.title')],
            [__('reports.fiscal_year'), $this->filters['fiscal_year'] ?? ''],
            [__('application_reports.status'), $statusLabel],
            [__('application_reports.date_from'), $this->filters['date_from']],
            [__('application_reports.date_to'), $this->filters['date_to']],
            [__('reports.total_records'), $this->rows->count()],
            [],
            [
                __('application_reports.track_id'),
                __('application_reports.full_name'),
                __('application_reports.amount_requested'),
                __('application_reports.amount_disbursed'),
                __('application_reports.bank_name'),
                __('application_reports.outstanding'),
                __('application_reports.amount_repaid'),
            ],
        ];

        foreach ($this->rows as $row) {
            $lines[] = [
                $row['track_id'],
                $row['full_name'],
                $row['amount_requested'],
                $row['amount_disbursed'],
                $row['bank_name'],
                $row['outstanding'],
                $row['amount_repaid'],
            ];
        }

        return $lines;
    }

    public function title(): string
    {
        return __('application_reports.title');
    }
}
