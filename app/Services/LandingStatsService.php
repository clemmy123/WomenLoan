<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanGroup;
use App\Models\Scopes\ApprovalLevelScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LandingStatsService
{
    protected int $cacheTtl = 300;

    /**
     * @return list<array{key: string, value: int, label: string, caption: string, theme: string}>
     */
    public function totals(): array
    {
        $counts = Cache::remember('landing.public_stats.v2', $this->cacheTtl, fn () => $this->rawCounts());

        return $this->formatStats($counts);
    }

    /**
     * @return array{beneficiaries: int, groups: int, applications: int, sectors: int, banks: int}
     */
    public function rawCounts(): array
    {
        return [
            'beneficiaries' => $this->beneficiariesCount(),
            'groups' => LoanGroup::query()->count(),
            'applications' => $this->loanQuery()->count(),
            'sectors' => $this->sectorsCount(),
            'banks' => $this->banksCount(),
        ];
    }

    protected function loanQuery(): Builder
    {
        return Loan::withoutGlobalScope(ApprovalLevelScope::class);
    }

    protected function disbursedLoanQuery(): Builder
    {
        return $this->loanQuery()
            ->where('status', 'disbursed')
            ->where('disbursed_amount', '>', 0);
    }

    protected function beneficiariesCount(): int
    {
        $individual = (int) (clone $this->disbursedLoanQuery())
            ->where('loan_type', 'individual')
            ->whereNotNull('applicant_id')
            ->distinct()
            ->count('applicant_id');

        $disbursedGroupIds = (clone $this->disbursedLoanQuery())
            ->where('loan_type', 'group')
            ->whereNotNull('loan_group_id')
            ->distinct()
            ->pluck('loan_group_id');

        $group = 0;

        foreach ($disbursedGroupIds as $groupId) {
            $members = (int) DB::table('loan_group_members')
                ->where('loan_group_id', $groupId)
                ->count();

            if ($members > 0) {
                $group += $members;

                continue;
            }

            $group += (int) DB::table('applicant_loan_group')
                ->where('loan_group_id', $groupId)
                ->count();
        }

        return $individual + $group;
    }

    protected function sectorsCount(): int
    {
        return (int) DB::table('business_details')
            ->whereIn('loan_id', (clone $this->disbursedLoanQuery())->select('id'))
            ->whereNotNull('business_sector')
            ->where('business_sector', '!=', '')
            ->distinct()
            ->count('business_sector');
    }

    protected function banksCount(): int
    {
        return (int) (clone $this->disbursedLoanQuery())
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->distinct()
            ->count('bank_name');
    }

    /**
     * @param  array{beneficiaries: int, groups: int, applications: int, sectors: int, banks: int}  $counts
     * @return list<array{key: string, value: int, label: string, caption: string, theme: string}>
     */
    protected function formatStats(array $counts): array
    {
        return [
            [
                'key' => 'beneficiaries',
                'value' => $counts['beneficiaries'],
                'label' => __('home.stats.beneficiaries_label'),
                'caption' => __('home.stats.beneficiaries_caption'),
                'theme' => 'violet',
            ],
            [
                'key' => 'groups',
                'value' => $counts['groups'],
                'label' => __('home.stats.groups_label'),
                'caption' => __('home.stats.groups_caption'),
                'theme' => 'cyan',
            ],
            [
                'key' => 'applications',
                'value' => $counts['applications'],
                'label' => __('home.stats.applications_label'),
                'caption' => __('home.stats.applications_caption'),
                'theme' => 'indigo',
            ],
            [
                'key' => 'sectors',
                'value' => $counts['sectors'],
                'label' => __('home.stats.sectors_label'),
                'caption' => __('home.stats.sectors_caption'),
                'theme' => 'cyan',
            ],
            [
                'key' => 'banks',
                'value' => $counts['banks'],
                'label' => __('home.stats.banks_label'),
                'caption' => __('home.stats.banks_caption'),
                'theme' => 'violet',
            ],
        ];
    }

    public static function flush(): void
    {
        Cache::forget('landing.public_stats.v1');
        Cache::forget('landing.public_stats.v2');
    }
}
