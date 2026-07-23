<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class UsersExport implements FromArray, WithTitle
{
    public function __construct(
        private Collection $rows,
        private array $filters,
    ) {}

    public function array(): array
    {
        $roleLabel = filled($this->filters['role'] ?? null)
            ? role_label($this->filters['role'])
            : __('admin.role_all');

        $statusLabel = match ($this->filters['status'] ?? null) {
            'active' => __('common.active'),
            'inactive' => __('common.inactive'),
            default => __('admin.status_all'),
        };

        $lines = [
            [__('nav.users')],
            [__('admin.search'), $this->filters['search'] ?? ''],
            [__('common.roles'), $roleLabel],
            [__('common.status'), $statusLabel],
            [__('admin.total_records'), $this->rows->count()],
            [],
            [
                __('admin.check_number'),
                __('common.name'),
                __('common.email'),
                __('common.phone'),
                __('common.roles'),
                __('common.status'),
            ],
        ];

        foreach ($this->rows as $row) {
            $lines[] = [
                $row['check_number'],
                $row['name'],
                $row['email'],
                $row['phone'],
                $row['roles'],
                $row['status'],
            ];
        }

        return $lines;
    }

    public function title(): string
    {
        return 'Users';
    }
}
