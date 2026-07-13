<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ByMonthlyExport implements FromArray, WithTitle
{
    public function __construct(
        private array $summary,
        private Collection $rows,
        private array $filters,
        private ?string $monthLabel = null,
    ) {}

    public function array(): array
    {
        $lines = [
            [__('by_monthly_reports.title')],
            [__('by_monthly_reports.month'), $this->monthLabel ?: __('by_monthly_reports.all_months')],
            [__('by_monthly_reports.date_from'), $this->filters['date_from'] ?? ''],
            [__('by_monthly_reports.date_to'), $this->filters['date_to'] ?? ''],
            [],
            [__('by_monthly_reports.summary')],
            [__('by_monthly_reports.total_disbursed'), $this->summary['total_disbursed']],
            [__('by_monthly_reports.people_financed'), ($this->summary['individual_count'] ?? 0) + ($this->summary['group_members_count'] ?? 0)],
            [__('by_monthly_reports.group_count'), $this->summary['group_count']],
            [__('by_monthly_reports.total_outstanding'), $this->summary['total_outstanding']],
            [__('by_monthly_reports.total_paid'), $this->summary['total_paid']],
            [],
            [
                __('by_monthly_reports.col_name'),
                __('dashboard.track_id'),
                __('by_monthly_reports.col_type'),
                __('by_monthly_reports.col_month'),
                __('by_monthly_reports.col_disbursed'),
                __('by_monthly_reports.col_outstanding'),
                __('by_monthly_reports.col_paid'),
                __('by_monthly_reports.col_phone'),
            ],
        ];

        foreach ($this->rows as $row) {
            $lines[] = [
                $row['name'],
                $row['track_id'],
                $row['loan_type_label'],
                $row['month_label'],
                $row['disbursed'],
                $row['outstanding'],
                $row['paid'],
                $row['phone'],
            ];
        }

        return $lines;
    }

    public function title(): string
    {
        return __('by_monthly_reports.title');
    }
}
