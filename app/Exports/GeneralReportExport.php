<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class GeneralReportExport implements FromArray, WithTitle
{
    public function __construct(
        private array $summary,
        private Collection $rows,
        private array $filters,
        private string $viewMode,
        private ?string $typeLabel = null,
    ) {}

    public function array(): array
    {
        $lines = [
            [__('general_reports.title')],
            [__('general_reports.loan_type'), $this->typeLabel ?: __('general_reports.all_types')],
            [__('reports.period'), __('reports.period_'.($this->filters['period'] ?? 'annually'))],
            [],
            [__('general_reports.summary')],
            [__('general_reports.women_count'), $this->summary['women_count']],
        ];

        if ($this->viewMode === 'all') {
            $lines[] = [__('general_reports.individual_count'), $this->summary['individual_count']];
            $lines[] = [__('general_reports.group_count'), $this->summary['group_count']];
            $lines[] = [__('general_reports.group_members_count'), $this->summary['group_members_count']];
        } elseif ($this->viewMode === 'group') {
            $lines[] = [__('general_reports.group_count'), $this->summary['group_count']];
            $lines[] = [__('general_reports.group_members_count'), $this->summary['group_members_count']];
        }

        array_push(
            $lines,
            [__('general_reports.bucket_applied'), $this->summary['applied'] ?? 0],
            [__('general_reports.bucket_received'), $this->summary['received'] ?? 0],
            [__('general_reports.bucket_processing'), $this->summary['processing'] ?? 0],
            [__('general_reports.bucket_missed'), $this->summary['missed'] ?? 0],
            [],
        );

        $lines[] = $this->headingRow();

        foreach ($this->rows as $row) {
            $lines[] = $this->dataRow($row);
        }

        return $lines;
    }

    public function title(): string
    {
        return __('general_reports.title');
    }

    /** @return list<string> */
    protected function headingRow(): array
    {
        return match ($this->viewMode) {
            'individual' => [
                __('general_reports.col_name'),
                __('general_reports.col_account'),
                __('dashboard.track_id'),
            ],
            'group' => [
                __('general_reports.col_group'),
                __('general_reports.col_members'),
                __('general_reports.col_account'),
                __('dashboard.track_id'),
            ],
            default => [
                __('general_reports.col_name'),
                __('general_reports.col_status'),
                __('general_reports.col_account'),
                __('dashboard.track_id'),
            ],
        };
    }

    /** @param array<string, mixed> $row */
    protected function dataRow(array $row): array
    {
        return match ($this->viewMode) {
            'individual' => [
                $row['name'],
                $row['account_number'],
                $row['track_id'],
            ],
            'group' => [
                $row['group_name'],
                $row['members_label'],
                $row['account_number'],
                $row['track_id'],
            ],
            default => [
                $row['name'],
                $row['loan_type_label'],
                $row['account_number'],
                $row['track_id'],
            ],
        };
    }
}
