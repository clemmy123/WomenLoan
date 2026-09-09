<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ByReportChartPayload
{
    /**
     * @return array{
     *     financial: array{labels: list<string>, data: list<float>},
     *     loan_type: array{labels: list<string>, data: list<int>},
     *     top_disbursed: array{labels: list<string>, data: list<float>}
     * }
     */
    public static function build(Collection $rows, ?array $summary = null): array
    {
        $financial = [
            'labels' => [
                __('reports.total_disbursed'),
                __('reports.total_paid'),
                __('reports.total_outstanding'),
            ],
            'data' => [
                round((float) $rows->sum('disbursed'), 2),
                round((float) $rows->sum('paid'), 2),
                round((float) $rows->sum('outstanding'), 2),
            ],
        ];

        $individualCount = (int) ($summary['individual_count'] ?? $rows->where('loan_type', 'individual')->count());
        $groupCount = (int) ($summary['group_count'] ?? $rows->where('loan_type', 'group')->unique('track_id')->count());

        $top = $rows->sortByDesc('disbursed')->take(8)->values();

        return [
            'financial' => $financial,
            'loan_type' => [
                'labels' => [
                    __('loans.types.individual'),
                    __('loans.types.group'),
                ],
                'data' => [$individualCount, $groupCount],
            ],
            'top_disbursed' => [
                'labels' => $top
                    ->pluck('name')
                    ->map(fn (string $name) => Str::limit($name, 28))
                    ->values()
                    ->all(),
                'data' => $top->pluck('disbursed')->map(fn ($v) => (float) $v)->values()->all(),
            ],
        ];
    }
}
