<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class AnalyticalDebtExport implements FromArray, WithTitle
{
    public function __construct(
        private string $mode,
        private array $summary,
        private Collection $rows,
        private array $filters,
    ) {}

    public function array(): array
    {
        $titleKey = $this->mode === 'overdue'
            ? 'analytical_reports.overdue_title'
            : 'analytical_reports.outstanding_title';

        $lines = [
            [__($titleKey)],
            [__('analytical_reports.fiscal_year'), ($this->filters['fiscal_year'] ?? null) === \App\Support\FiscalYear::ALL_KEY ? __('analytical_reports.all_years') : ($this->filters['fiscal_year'] ?? '')],
            [__('analytical_reports.date_from'), $this->filters['date_from'] ?? ''],
            [__('analytical_reports.date_to'), $this->filters['date_to'] ?? ''],
            [],
            [__('analytical_reports.summary')],
            [__('analytical_reports.debt_count'), $this->summary['count']],
            [__('analytical_reports.col_disbursed'), $this->summary['total_disbursed']],
            [__('analytical_reports.col_outstanding'), $this->summary['total_outstanding']],
            [__('analytical_reports.total_paid'), $this->summary['total_paid']],
            [__('analytical_reports.average_elapsed'), $this->summary['average_elapsed_days']],
            [],
            [
                __('analytical_reports.col_name'),
                __('analytical_reports.col_disbursed'),
                __('analytical_reports.col_outstanding'),
                __('analytical_reports.col_elapsed'),
                __('dashboard.track_id'),
                __('analytical_reports.col_phone'),
                __('analytical_reports.col_location'),
            ],
        ];

        foreach ($this->rows as $row) {
            $lines[] = [
                $row['name'],
                $row['disbursed'],
                $row['outstanding'],
                $row['elapsed_label'],
                $row['track_id'],
                $row['phone'],
                $row['location'],
            ];
        }

        return $lines;
    }

    public function title(): string
    {
        return $this->mode === 'overdue'
            ? __('analytical_reports.overdue_title')
            : __('analytical_reports.outstanding_title');
    }
}
