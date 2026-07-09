<?php

namespace Tests\Unit;

use App\Support\AgeCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AgeCalculatorTest extends TestCase
{
    public function test_age_increases_on_birthday_not_calendar_year(): void
    {
        $dob = Carbon::parse('1995-07-07');

        $this->assertSame(30, AgeCalculator::years($dob, Carbon::parse('2026-07-06')));
        $this->assertSame(31, AgeCalculator::years($dob, Carbon::parse('2026-07-07')));
        $this->assertSame(31, AgeCalculator::years($dob, Carbon::parse('2026-07-09')));
    }

    public function test_age_range_bounds_match_birthday_logic(): void
    {
        $asOf = Carbon::parse('2026-07-09');
        $dob = Carbon::parse('1995-07-07'); // age 31 on asOf

        $this->assertTrue(AgeCalculator::matchesRange($dob, 31, 31, $asOf));
        $this->assertFalse(AgeCalculator::matchesRange($dob, 30, 30, $asOf));
        $this->assertTrue(AgeCalculator::matchesRange($dob, 30, 35, $asOf));

        $latestFor31 = AgeCalculator::latestDobForMinAge(31, $asOf);
        $this->assertTrue($dob->lessThanOrEqualTo($latestFor31));

        $exclusiveForMax30 = AgeCalculator::earliestExclusiveDobForMaxAge(30, $asOf);
        $this->assertFalse($dob->greaterThan($exclusiveForMax30));
    }

    public function test_person_day_before_birthday_is_still_previous_age(): void
    {
        $dob = Carbon::parse('1995-07-10');
        $asOf = Carbon::parse('2026-07-09');

        $this->assertSame(30, AgeCalculator::years($dob, $asOf));
        $this->assertTrue(AgeCalculator::matchesRange($dob, 30, 30, $asOf));
    }
}
