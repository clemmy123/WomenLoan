<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class AnalyticalOverviewExport implements FromArray, WithTitle
{
    public function __construct(
        private array $summary,
        private Collection $individuals,
        private Collection $groups,
        private array $filters,
    ) {}

    public function array(): array
    {
        $lines = [
            [__('analytical_reports.overview_title')],
            [__('analytical_reports.fiscal_year'), $this->filters['fiscal_year'] ?? ''],
            [__('analytical_reports.date_from'), $this->filters['date_from']],
            [__('analytical_reports.date_to'), $this->filters['date_to']],
            [
                ! empty($this->filters['quarter'])
                    ? __('analytical_reports.quarter')
                    : __('analytical_reports.period'),
                ! empty($this->filters['quarter'])
                    ? __('analytical_reports.period_'.$this->filters['quarter'])
                    : __('analytical_reports.period_'.$this->filters['period']),
            ],
            [],
            [__('analytical_reports.summary')],
            [__('analytical_reports.individual_count'), $this->summary['individual_count']],
            [__('analytical_reports.individual_disbursed'), $this->summary['individual_disbursed']],
            [__('analytical_reports.individual_paid'), $this->summary['individual_paid']],
            [__('analytical_reports.group_count'), $this->summary['group_count']],
            [__('analytical_reports.group_disbursed'), $this->summary['group_disbursed']],
            [__('analytical_reports.group_paid'), $this->summary['group_paid']],
            [__('analytical_reports.total_disbursed'), $this->summary['total_disbursed']],
            [__('analytical_reports.total_paid'), $this->summary['total_paid']],
            [__('analytical_reports.total_outstanding'), $this->summary['total_outstanding']],
            [],
            [__('analytical_reports.individual_repayments')],
            [
                __('analytical_reports.col_name'),
                __('analytical_reports.col_bank'),
                __('analytical_reports.col_phone'),
                __('analytical_reports.col_disbursed'),
                __('analytical_reports.col_paid'),
                __('analytical_reports.col_paid_on'),
                __('analytical_reports.col_outstanding'),
                __('dashboard.track_id'),
            ],
        ];

        foreach ($this->individuals as $row) {
            $lines[] = [
                $row['name'],
                $row['bank'],
                $row['phone'],
                $row['disbursed'],
                $row['paid'],
                $row['paid_on'],
                $row['outstanding'],
                $row['track_id'],
            ];
        }

        $lines[] = [];
        $lines[] = [__('analytical_reports.group_repayments')];
        $lines[] = [
            __('analytical_reports.col_group_name'),
            __('analytical_reports.col_members'),
            __('analytical_reports.col_location'),
            __('analytical_reports.col_disbursed'),
            __('analytical_reports.col_paid'),
            __('analytical_reports.col_outstanding'),
            __('dashboard.track_id'),
        ];

        foreach ($this->groups as $row) {
            $lines[] = [
                $row['name'],
                $row['members_count'],
                $row['location'],
                $row['disbursed'],
                $row['paid'],
                $row['outstanding'],
                $row['track_id'],
            ];
        }

        return $lines;
    }

    public function title(): string
    {
        return __('analytical_reports.overview_title');
    }
}
