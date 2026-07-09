<?php

namespace App\Support;

use Carbon\Carbon;

class FiscalYear
{
    /** Earliest selectable FY start year (FY 2018/2019). */
    public const EARLIEST_START = 2018;

    public static function currentKey(?Carbon $asOf = null): string
    {
        return self::key(self::startYear($asOf ?? now()));
    }

    /**
     * FY keys available for selection: past years through the current FY only.
     * Future FYs appear automatically once July 1 of that year is reached.
     *
     * @return array<string, string>
     */
    public static function options(?Carbon $asOf = null): array
    {
        $asOf ??= now();
        $currentStart = self::startYear($asOf);
        $options = [];

        for ($start = self::EARLIEST_START; $start <= $currentStart; $start++) {
            $key = self::key($start);
            $options[$key] = $key;
        }

        return array_reverse($options, true);
    }

    public static function normalize(?string $value, ?Carbon $asOf = null): string
    {
        $options = self::options($asOf);
        if ($value && isset($options[$value])) {
            return $value;
        }

        return self::currentKey($asOf);
    }

    /**
     * @return array{0: string, 1: string} [from, to] inclusive (Jul 1 – Jun 30)
     */
    public static function dateRange(string $fiscalYear): array
    {
        $startYear = (int) explode('/', $fiscalYear)[0];

        return [
            sprintf('%04d-07-01', $startYear),
            sprintf('%04d-06-30', $startYear + 1),
        ];
    }

    public static function startYear(Carbon $asOf): int
    {
        return $asOf->month >= 7 ? $asOf->year : $asOf->year - 1;
    }

    public static function key(int $startYear): string
    {
        return $startYear.'/'.($startYear + 1);
    }

    /**
     * Resolve a period window inside the selected fiscal year.
     *
     * @return array{0: string, 1: string}
     */
    public static function periodRangeWithin(string $period, string $fyFrom, string $fyTo): array
    {
        $fyStart = Carbon::parse($fyFrom)->startOfDay();
        $fyEnd = Carbon::parse($fyTo)->endOfDay();
        $today = now()->startOfDay();

        if ($today->lt($fyStart)) {
            $today = $fyStart->copy();
        } elseif ($today->gt($fyEnd)) {
            $today = $fyEnd->copy();
        }

        $startYear = (int) $fyStart->year;
        $endYear = $startYear + 1;

        return match ($period) {
            'daily' => [$today->toDateString(), $today->toDateString()],
            'weekly' => [
                max($fyFrom, $today->copy()->startOfWeek()->toDateString()),
                $today->toDateString(),
            ],
            'semi_annual' => [
                $today->month >= 7
                    ? $fyFrom
                    : sprintf('%04d-01-01', $endYear),
                $today->toDateString(),
            ],
            'quarterly' => [
                max($fyFrom, $today->copy()->firstOfQuarter()->toDateString()),
                min($fyTo, $today->copy()->lastOfQuarter()->toDateString()),
            ],
            'annually' => [$fyFrom, $fyTo],
            'q1' => [
                sprintf('%04d-01-01', $endYear),
                sprintf('%04d-03-31', $endYear),
            ],
            'q2' => [
                sprintf('%04d-04-01', $endYear),
                sprintf('%04d-06-30', $endYear),
            ],
            'q3' => [
                sprintf('%04d-07-01', $startYear),
                sprintf('%04d-09-30', $startYear),
            ],
            'q4' => [
                sprintf('%04d-10-01', $startYear),
                sprintf('%04d-12-31', $startYear),
            ],
            default => [
                max($fyFrom, $today->copy()->startOfMonth()->toDateString()),
                $today->toDateString(),
            ],
        };
    }

    /**
     * Clamp custom dates into the fiscal year bounds.
     *
     * @return array{0: string, 1: string}
     */
    public static function clampDates(string $from, string $to, string $fyFrom, string $fyTo): array
    {
        $from = max($fyFrom, $from);
        $to = min($fyTo, $to);

        if ($from > $to) {
            return [$fyFrom, $fyTo];
        }

        return [$from, $to];
    }
}
