<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class AnalyticalDebtReportService
{
    public const MODE_OUTSTANDING = 'outstanding';

    public const MODE_OVERDUE = 'overdue';

    public const SORTS = [
        'outstanding_desc',
        'disbursed_desc',
        'elapsed_desc',
        'name_asc',
        'newest',
        'oldest',
    ];

    public function __construct(
        private AnalyticalReportService $analytical,
        private RepaymentScheduleService $schedules,
    ) {}

    public function normalizeFilters(array $input): array
    {
        $filters = $this->analytical->normalizeFilters($input);

        $sort = $input['sort'] ?? 'outstanding_desc';
        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'outstanding_desc';
        }
        $filters['sort'] = $sort;

        return $filters;
    }

    public function fiscalYearOptions(?Carbon $asOf = null): array
    {
        return $this->analytical->fiscalYearOptions($asOf);
    }

    public function currentFiscalYearKey(?Carbon $asOf = null): string
    {
        return $this->analytical->currentFiscalYearKey($asOf);
    }

    public function regions()
    {
        return $this->analytical->regions();
    }

    public function sortOptions(): array
    {
        return collect(self::SORTS)
            ->mapWithKeys(fn (string $sort) => [$sort => __('analytical_reports.sort_'.$sort)])
            ->all();
    }

    public function summary(array $filters, string $mode): array
    {
        $rows = $this->allRows($filters, $mode);

        $totalDisbursed = round($rows->sum('disbursed'), 2);
        $totalOutstanding = round($rows->sum('outstanding'), 2);
        $totalPaid = round($rows->sum('paid'), 2);
        $count = $rows->count();
        $avgDays = $count > 0 ? (int) round($rows->avg('elapsed_days')) : 0;

        return [
            'count' => $count,
            'total_disbursed' => $totalDisbursed,
            'total_outstanding' => $totalOutstanding,
            'total_paid' => $totalPaid,
            'average_elapsed_days' => $avgDays,
            'collection_rate' => $totalDisbursed > 0
                ? (int) min(100, round(($totalPaid / ($totalPaid + $totalOutstanding)) * 100))
                : 0,
        ];
    }

    public function paginatedRows(array $filters, string $mode, int $perPage = 15): LengthAwarePaginator
    {
        $rows = $this->allRows($filters, $mode)->values();
        $page = max(1, (int) request()->query('page', 1));
        $slice = $rows->forPage($page, $perPage)->values();

        return (new Paginator(
            $slice,
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        ))->withQueryString();
    }

    public function allRows(array $filters, string $mode): Collection
    {
        $loans = $this->candidateLoans($filters);

        $rows = $loans
            ->map(fn (Loan $loan) => $this->mapRow($loan, $mode))
            ->filter()
            ->values();

        return $this->sortRows($rows, $filters['sort'] ?? 'outstanding_desc');
    }

    public function exportFilename(string $mode, string $extension): string
    {
        $slug = $mode === self::MODE_OVERDUE ? 'overdue-payments' : 'outstanding-debts';

        return 'wdf-analytical-'.$slug.'-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    protected function candidateLoans(array $filters): Collection
    {
        // Reuse analytical base filters via reflection-safe public path:
        // outstanding debts are a subset of disbursed loans.
        $query = Loan::withoutGlobalScope(\App\Models\Scopes\ApprovalLevelScope::class)
            ->with([
                'applicant:id,full_name,first_name,last_name,phone',
                'group:id,name,phone',
                'businessDetails.region:id,name',
                'businessDetails.district:id,name',
                'businessDetails.council:id,name',
                'businessDetails.ward:id,name',
                'businessDetails.street:id,name',
                'loanPayments',
            ])
            ->where('status', 'disbursed')
            ->where('disbursed_amount', '>', 0)
            ->whereHas('loanPayments', fn ($q) => $q->where('outstanding_debt', '>', 0));

        $user = auth()->user();
        if ($user?->hasRole('applicant')) {
            $query->where('user_id', $user->id);
        } elseif ($user?->hasRole(['cdo_ward', 'cdo_council', 'cdo_region'])) {
            app(CdoLoanScopeService::class)->applyBusinessDetailsScope($query, $user);
        }

        if ($filters['date_from']) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('date_issued', '>=', $filters['date_from'])
                    ->orWhere(function ($inner) use ($filters) {
                        $inner->whereNull('date_issued')
                            ->whereDate('updated_at', '>=', $filters['date_from']);
                    });
            });
        }

        if ($filters['date_to']) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('date_issued', '<=', $filters['date_to'])
                    ->orWhere(function ($inner) use ($filters) {
                        $inner->whereNull('date_issued')
                            ->whereDate('updated_at', '<=', $filters['date_to']);
                    });
            });
        }

        $hasGeoFilter = $filters['region_id']
            || $filters['district_id']
            || $filters['council_id']
            || $filters['ward_id']
            || $filters['street_id'];

        if ($hasGeoFilter) {
            $query->whereHas('businessDetails', function ($q) use ($filters) {
                if ($filters['region_id']) {
                    $q->where('region_id', $filters['region_id']);
                }
                if ($filters['district_id']) {
                    $q->where('district_id', $filters['district_id']);
                }
                if ($filters['council_id']) {
                    $q->where('council_id', $filters['council_id']);
                }
                if ($filters['ward_id']) {
                    $q->where('ward_id', $filters['ward_id']);
                }
                if ($filters['street_id']) {
                    $q->where('street_id', $filters['street_id']);
                }
            });
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('loan_track_id', 'like', $like)
                    ->orWhereHas('applicant', fn ($a) => $a
                        ->where('full_name', 'like', $like)
                        ->orWhere('phone', 'like', $like))
                    ->orWhereHas('group', fn ($g) => $g
                        ->where('name', 'like', $like)
                        ->orWhere('phone', 'like', $like));
            });
        }

        return $query->get();
    }

    protected function mapRow(Loan $loan, string $mode): ?array
    {
        $payment = $this->paymentLedger($loan);
        $outstanding = $this->outstandingAmount($loan, $payment);

        if ($outstanding <= 0) {
            return null;
        }

        $overdueSince = $this->overdueSince($payment);
        $isOverdue = $overdueSince !== null;

        if ($mode === self::MODE_OVERDUE && ! $isOverdue) {
            return null;
        }

        $anchor = $mode === self::MODE_OVERDUE
            ? $overdueSince
            : $this->elapsedAnchor($loan, $payment);

        $elapsedDays = $anchor
            ? max(0, $anchor->copy()->startOfDay()->diffInDays(now()->startOfDay()))
            : 0;

        return [
            'track_id' => $loan->loan_track_id,
            'hashid' => $loan->hashid,
            'name' => $loan->loan_type === 'group'
                ? ($loan->group?->name ?? __('common.na'))
                : ($loan->applicant?->full_name ?? __('common.na')),
            'loan_type' => $loan->loan_type,
            'phone' => $loan->loan_type === 'group'
                ? ($loan->group?->phone ?: __('common.na'))
                : ($loan->applicant?->phone ?: __('common.na')),
            'disbursed' => max(0.0, (float) ($loan->disbursed_amount ?? 0)),
            'paid' => (float) ($payment?->amount_paid ?? 0),
            'outstanding' => $outstanding,
            'elapsed_days' => $elapsedDays,
            'elapsed_label' => $this->formatElapsed($elapsedDays),
            'due_date' => $overdueSince?->translatedFormat('d M Y') ?? '—',
            'date_issued' => ($loan->date_issued ?? $loan->updated_at)?->translatedFormat('d M Y') ?? '—',
            'location' => $this->locationLabel($loan),
            'is_overdue' => $isOverdue,
        ];
    }

    protected function sortRows(Collection $rows, string $sort): Collection
    {
        return match ($sort) {
            'disbursed_desc' => $rows->sortByDesc('disbursed')->values(),
            'elapsed_desc' => $rows->sortByDesc('elapsed_days')->values(),
            'name_asc' => $rows->sortBy(fn ($row) => mb_strtolower((string) $row['name']))->values(),
            'newest' => $rows->sortByDesc('track_id')->values(),
            'oldest' => $rows->sortBy('track_id')->values(),
            default => $rows->sortByDesc('outstanding')->values(),
        };
    }

    protected function overdueSince(?LoanPayment $payment): ?Carbon
    {
        if (! $payment || (float) $payment->outstanding_debt <= 0) {
            return null;
        }

        $today = now()->startOfDay();
        $earliest = null;

        foreach ($this->schedules->installmentSchedule($payment) as $installment) {
            $status = $installment['status'] ?? 'pending';
            if (! in_array($status, ['pending', 'partial'], true)) {
                continue;
            }

            $due = $installment['due_date'] ?? null;
            if (! $due) {
                continue;
            }

            $dueDate = Carbon::parse($due)->startOfDay();
            if ($dueDate->lt($today)) {
                $earliest = $earliest === null || $dueDate->lt($earliest) ? $dueDate : $earliest;
            }
        }

        if ($earliest) {
            return $earliest;
        }

        // Fallback: whole repayment term ended with balance remaining.
        if ($payment->end_date) {
            $end = $payment->end_date instanceof Carbon
                ? $payment->end_date->copy()->startOfDay()
                : Carbon::parse($payment->end_date)->startOfDay();

            if ($end->lt($today)) {
                return $end;
            }
        }

        return null;
    }

    protected function elapsedAnchor(Loan $loan, ?LoanPayment $payment): ?Carbon
    {
        if ($loan->date_issued) {
            return Carbon::parse($loan->date_issued)->startOfDay();
        }

        if ($payment?->start_date) {
            return $payment->start_date instanceof Carbon
                ? $payment->start_date->copy()->startOfDay()
                : Carbon::parse($payment->start_date)->startOfDay();
        }

        return $loan->updated_at?->copy()->startOfDay();
    }

    protected function formatElapsed(int $days): string
    {
        if ($days <= 0) {
            return __('analytical_reports.elapsed_today');
        }

        if ($days < 30) {
            return trans_choice('analytical_reports.elapsed_days', $days, ['count' => $days]);
        }

        $months = intdiv($days, 30);
        $remDays = $days % 30;

        if ($months >= 12) {
            $years = intdiv($months, 12);
            $remMonths = $months % 12;

            if ($remMonths > 0) {
                return __('analytical_reports.elapsed_years_months', [
                    'years' => $years,
                    'months' => $remMonths,
                ]);
            }

            return trans_choice('analytical_reports.elapsed_years', $years, ['count' => $years]);
        }

        if ($remDays > 0) {
            return __('analytical_reports.elapsed_months_days', [
                'months' => $months,
                'days' => $remDays,
            ]);
        }

        return trans_choice('analytical_reports.elapsed_months', $months, ['count' => $months]);
    }

    protected function paymentLedger(Loan $loan): ?LoanPayment
    {
        if ($loan->relationLoaded('loanPayments')) {
            return $loan->loanPayments->sortBy('id')->first();
        }

        return $loan->loanPayments()->orderBy('id')->first();
    }

    protected function outstandingAmount(Loan $loan, ?LoanPayment $payment): float
    {
        if ($payment) {
            return max(0.0, (float) ($payment->outstanding_debt ?? 0));
        }

        return max(0.0, (float) ($loan->disbursed_amount ?? 0));
    }

    protected function locationLabel(Loan $loan): string
    {
        $parts = array_filter([
            $loan->businessDetails?->region?->name,
            $loan->businessDetails?->district?->name,
            $loan->businessDetails?->council?->name,
            $loan->businessDetails?->ward?->name,
            $loan->businessDetails?->street?->name,
        ]);

        return $parts ? implode(', ', $parts) : __('common.na');
    }
}
