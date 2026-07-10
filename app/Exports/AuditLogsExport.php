<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class AuditLogsExport implements FromArray, WithTitle
{
    public function __construct(
        private Collection $rows,
        private array $filters,
    ) {}

    public function array(): array
    {
        $lines = [
            [__('nav.audit_logs')],
            [__('audit.date_from'), $this->filters['date_from'] ?? ''],
            [__('audit.date_to'), $this->filters['date_to'] ?? ''],
            [__('audit.event'), $this->filters['event'] ?? __('audit.all_events')],
            [__('audit.search'), $this->filters['search'] ?? ''],
            [__('audit.total_records'), $this->rows->count()],
            [],
            [
                __('audit.when'),
                __('audit.who'),
                __('audit.event'),
                __('audit.what'),
                __('audit.description'),
            ],
        ];

        foreach ($this->rows as $row) {
            $lines[] = [
                $row['when'],
                $row['who'],
                $row['event'],
                $row['what'],
                $row['description'],
            ];
        }

        return $lines;
    }

    public function title(): string
    {
        return 'Audit Logs';
    }
}
