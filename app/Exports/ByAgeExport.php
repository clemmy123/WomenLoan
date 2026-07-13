<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ByAgeExport implements FromArray, WithTitle
{
    public function __construct(
        private array $summary,
        private Collection $rows,
        private array $filters,
        private ?string $regionLabel = null,
    ) {}

    public function array(): array
    {
        $lines = [
            [__('by_age_reports.title')],
            [__('by_age_reports.region'), $this->regionLabel ?: __('by_age_reports.all_regions')],
            [__('by_age_reports.age_min'), $this->filters['age_min'] ?? ''],
            [__('by_age_reports.age_max'), $this->filters['age_max'] ?? ''],
            [],
            [__('by_age_reports.summary')],
            [__('by_age_reports.total_disbursed'), $this->summary['total_disbursed']],
            [__('by_age_reports.people_financed'), $this->summary['count']],
            [__('by_age_reports.group_count'), $this->summary['group_count']],
            [__('by_age_reports.total_outstanding'), $this->summary['total_outstanding']],
            [__('by_age_reports.total_paid'), $this->summary['total_paid']],
            [],
            [
                __('by_age_reports.col_name'),
                __('dashboard.track_id'),
                __('by_age_reports.col_age'),
                __('by_age_reports.col_membership'),
                __('by_age_reports.col_region'),
                __('by_age_reports.col_disbursed'),
                __('by_age_reports.col_outstanding'),
                __('by_age_reports.col_paid'),
                __('by_age_reports.col_phone'),
            ],
        ];

        foreach ($this->rows as $row) {
            $lines[] = [
                $row['name'],
                $row['track_id'],
                $row['age'] ?? '',
                $row['loan_type_label'],
                $row['region'],
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
        return __('by_age_reports.title');
    }
}
