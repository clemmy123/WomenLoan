<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class AgeCalculator
{
    /**
     * Completed years of age on $asOf (birthday-aware).
     * Example: born 1995-07-07 is 30 on 2026-07-06 and 31 on 2026-07-07.
     */
    public static function years(?CarbonInterface $dob, ?CarbonInterface $asOf = null): ?int
    {
        if ($dob === null) {
            return null;
        }

        $asOf ??= Carbon::now();
        $birth = Carbon::parse($dob->format('Y-m-d'))->startOfDay();
        $on = Carbon::parse($asOf->format('Y-m-d'))->startOfDay();

        if ($birth->greaterThan($on)) {
            return null;
        }

        // DateInterval::y is completed years (increments only on/after birthday).
        return $birth->diff($on)->y;
    }

    /**
     * Latest DOB that still qualifies for age >= $minAge on $asOf.
     */
    public static function latestDobForMinAge(int $minAge, ?CarbonInterface $asOf = null): Carbon
    {
        $asOf = Carbon::parse(($asOf ?? Carbon::now())->format('Y-m-d'))->startOfDay();

        return $asOf->copy()->subYears($minAge);
    }

    /**
     * Earliest DOB that still qualifies for age <= $maxAge on $asOf
     * (exclusive lower bound: dob must be after this date).
     */
    public static function earliestExclusiveDobForMaxAge(int $maxAge, ?CarbonInterface $asOf = null): Carbon
    {
        $asOf = Carbon::parse(($asOf ?? Carbon::now())->format('Y-m-d'))->startOfDay();

        return $asOf->copy()->subYears($maxAge + 1);
    }

    public static function matchesRange(
        ?CarbonInterface $dob,
        ?int $minAge,
        ?int $maxAge,
        ?CarbonInterface $asOf = null,
    ): bool {
        $age = self::years($dob, $asOf);

        if ($age === null) {
            return false;
        }

        if ($minAge !== null && $age < $minAge) {
            return false;
        }

        if ($maxAge !== null && $age > $maxAge) {
            return false;
        }

        return true;
    }
}
