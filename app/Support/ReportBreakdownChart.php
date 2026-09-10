<?php

namespace App\Support;

class ReportBreakdownChart
{
    /**
     * @param  array<string, array{disbursed: float, outstanding: float, count: int}>  $totals
     * @return array{
     *     labels: list<string>,
     *     disbursed: list<float>,
     *     outstanding: list<float>,
     *     count: list<int>,
     *     legend_disbursed: string,
     *     legend_outstanding: string,
     *     legend_count: string
     * }
     */
    public static function fromTotals(
        array $totals,
        string $disbursedLegend,
        string $outstandingLegend,
        string $countLegend,
        bool $sortByDisbursed = true,
    ): array {
        if ($sortByDisbursed) {
            uasort($totals, fn (array $a, array $b) => $b['disbursed'] <=> $a['disbursed']);
        }

        return [
            'labels' => array_keys($totals),
            'disbursed' => array_map(fn (array $row) => round($row['disbursed'], 2), $totals),
            'outstanding' => array_map(fn (array $row) => round($row['outstanding'], 2), $totals),
            'count' => array_map(fn (array $row) => (int) $row['count'], $totals),
            'legend_disbursed' => $disbursedLegend,
            'legend_outstanding' => $outstandingLegend,
            'legend_count' => $countLegend,
        ];
    }

    /** @return array{disbursed: float, outstanding: float, count: int} */
    public static function emptyRow(): array
    {
        return [
            'disbursed' => 0.0,
            'outstanding' => 0.0,
            'count' => 0,
        ];
    }

    public static function accumulate(
        array &$totals,
        string $label,
        float $disbursed,
        float $outstanding,
        int $countDelta = 1,
    ): void {
        if (! isset($totals[$label])) {
            $totals[$label] = self::emptyRow();
        }

        $totals[$label]['disbursed'] += $disbursed;
        $totals[$label]['outstanding'] += $outstanding;
        $totals[$label]['count'] += $countDelta;
    }
}
