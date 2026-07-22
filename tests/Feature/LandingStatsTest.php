<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanGroup;
use App\Models\LoanGroupMember;
use App\Models\Scopes\ApprovalLevelScope;
use App\Services\LandingStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LandingStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedApplication();
        Cache::flush();
    }

    public function test_landing_page_shows_public_stats_from_database(): void
    {
        $stats = app(LandingStatsService::class)->totals();

        $response = $this->get(route('home'));

        $response->assertOk();

        foreach ($stats as $stat) {
            $response->assertSee(number_format($stat['value']), false);
            $response->assertSee($stat['label'], false);
        }
    }

    public function test_landing_stats_match_seeded_totals(): void
    {
        $stats = collect(app(LandingStatsService::class)->rawCounts());

        $this->assertSame(12, $stats['applications']);
        $this->assertSame(2, $stats['groups']);
        $this->assertSame(3, $stats['beneficiaries']);
        $this->assertSame(1, $stats['sectors']);
        $this->assertSame(1, $stats['banks']);
    }

    public function test_group_beneficiaries_are_not_double_counted_for_multiple_disbursed_loans(): void
    {
        $group = LoanGroup::query()->firstOrFail();

        Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_group_id', $group->id)
            ->where('status', 'disbursed')
            ->firstOrFail()
            ->replicate()
            ->fill([
                'loan_track_id' => 'WL999901',
                'status' => 'disbursed',
                'disbursed_amount' => 1500000,
                'current_step' => 10,
            ])
            ->save();

        Cache::flush();

        $this->assertSame(3, app(LandingStatsService::class)->rawCounts()['beneficiaries']);
    }

    public function test_group_beneficiaries_prefer_registered_members_over_attached_applicants(): void
    {
        $group = LoanGroup::query()->firstOrFail();

        LoanGroupMember::create([
            'loan_group_id' => $group->id,
            'first_name' => 'Asha',
            'last_name' => 'Mwita',
            'full_name' => 'Asha Mwita',
            'nin' => '1990010112345678901',
            'phone' => '255700000001',
        ]);

        LoanGroupMember::create([
            'loan_group_id' => $group->id,
            'first_name' => 'Neema',
            'last_name' => 'Joseph',
            'full_name' => 'Neema Joseph',
            'nin' => '1990020212345678902',
            'phone' => '255700000002',
        ]);

        Cache::flush();

        $this->assertSame(2, app(LandingStatsService::class)->rawCounts()['beneficiaries']);
    }
}
