<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportsExport implements FromArray, WithTitle
{
    public function __construct(
        private array $summary,
        private Collection $rows,
        private array $filters,
    ) {}

    public function array(): array
    {
        $lines = [
            [__('reports.title')],
            [__('reports.fiscal_year'), $this->filters['fiscal_year'] ?? ''],
            [__('reports.date_from'), $this->filters['date_from']],
            [__('reports.date_to'), $this->filters['date_to']],
            [__('reports.total_records'), $this->summary['count']],
            [__('reports.total_disbursed'), $this->summary['total_disbursed']],
            [__('reports.total_paid'), $this->summary['total_paid']],
            [__('reports.total_outstanding'), $this->summary['total_outstanding']],
            [],
            [
                __('reports.name'),
                __('dashboard.track_id'),
                __('common.region'),
                __('common.type'),
                __('reports.disbursed'),
                __('reports.paid'),
                __('reports.outstanding'),
                __('dashboard.date'),
            ],
        ];

        foreach ($this->rows as $row) {
            $lines[] = [
                $row['name'],
                $row['track_id'],
                $row['region'] ?? '',
                $row['loan_type'],
                $row['disbursed'],
                $row['paid'],
                $row['outstanding'],
                $row['date'],
            ];
        }

        return $lines;
    }

    public function title(): string
    {
        return __('reports.title');
    }
}
