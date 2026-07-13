<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ByBankExport implements FromArray, WithTitle
{
    public function __construct(
        private array $summary,
        private Collection $rows,
        private array $filters,
        private ?string $bankLabel = null,
    ) {}

    public function array(): array
    {
        $lines = [
            [__('by_bank_reports.title')],
            [__('by_bank_reports.bank'), $this->bankLabel ?: __('by_bank_reports.all_banks')],
            [__('by_bank_reports.fiscal_year'), ($this->filters['fiscal_year'] ?? null) === \App\Support\FiscalYear::ALL_KEY ? __('reports.all_years') : ($this->filters['fiscal_year'] ?? '')],
            [__('by_bank_reports.date_from'), $this->filters['date_from'] ?? ''],
            [__('by_bank_reports.date_to'), $this->filters['date_to'] ?? ''],
            [],
            [__('by_bank_reports.summary')],
            [__('by_bank_reports.total_disbursed'), $this->summary['total_disbursed']],
            [__('by_bank_reports.people_financed'), ($this->summary['individual_count'] ?? 0) + ($this->summary['group_members_count'] ?? 0)],
            [__('by_bank_reports.group_count'), $this->summary['group_count']],
            [__('by_bank_reports.total_outstanding'), $this->summary['total_outstanding']],
            [__('by_bank_reports.total_paid'), $this->summary['total_paid']],
            [],
            [
                __('by_bank_reports.col_name'),
                __('dashboard.track_id'),
                __('by_bank_reports.col_type'),
                __('by_bank_reports.col_bank'),
                __('by_bank_reports.col_disbursed'),
                __('by_bank_reports.col_outstanding'),
                __('by_bank_reports.col_paid'),
                __('by_bank_reports.col_phone'),
            ],
        ];

        foreach ($this->rows as $row) {
            $lines[] = [
                $row['name'],
                $row['track_id'],
                $row['loan_type_label'],
                $row['bank'],
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
        return __('by_bank_reports.title');
    }
}
