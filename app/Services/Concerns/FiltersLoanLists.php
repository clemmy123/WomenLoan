<?php

namespace App\Services\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait FiltersLoanLists
{
    public function listSortOptions(): array
    {
        return [
            'newest' => __('dashboard.sort_newest'),
            'oldest' => __('dashboard.sort_oldest'),
            'amount_high' => __('dashboard.sort_amount_high'),
            'amount_low' => __('dashboard.sort_amount_low'),
            'track_id' => __('dashboard.sort_track_id'),
            'step' => __('dashboard.sort_step'),
            'status' => __('dashboard.sort_status'),
        ];
    }

    public function listStatusOptions(?\App\Models\User $user = null): array
    {
        $user ??= auth()->user();

        if ($user?->hasRole('chief')) {
            return [
                '' => __('loans.all_statuses'),
                'approved' => loan_status_label('approved'),
                'ready_for_disbursement' => loan_status_label('ready_for_disbursement'),
                'disbursed' => loan_status_label('disbursed'),
            ];
        }

        if ($user?->hasRole('accountant')) {
            return [
                '' => __('loans.all_statuses'),
                'ready_for_disbursement' => loan_status_label('ready_for_disbursement'),
                'disbursed' => loan_status_label('disbursed'),
            ];
        }

        return [
            '' => __('loans.all_statuses'),
            'pending' => loan_status_label('pending'),
            'received' => loan_status_label('received'),
            'in_review' => loan_status_label('in_review'),
            'awaiting_applicant' => loan_status_label('awaiting_applicant'),
            'declined_by_applicant' => loan_status_label('declined_by_applicant'),
            'approved' => loan_status_label('approved'),
            'ready_for_disbursement' => loan_status_label('ready_for_disbursement'),
            'disbursed' => loan_status_label('disbursed'),
            'rejected' => loan_status_label('rejected'),
        ];
    }

    public function normalizeListSort(?string $sort): string
    {
        $allowed = array_keys($this->listSortOptions());

        return in_array($sort, $allowed, true) ? $sort : 'newest';
    }

    public function normalizeListStatus(?string $status): string
    {
        $status = trim((string) $status);
        $allowed = array_keys(array_filter(
            $this->listStatusOptions(),
            fn ($label, $value) => $value !== '',
            ARRAY_FILTER_USE_BOTH
        ));

        return in_array($status, $allowed, true) ? $status : '';
    }

    protected function applyListSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $term = '%'.addcslashes($search, '%_\\').'%';

        $query->where(function (Builder $inner) use ($term) {
            $inner->where('loan_track_id', 'like', $term)
                ->orWhereHas('applicant', fn (Builder $applicant) => $applicant
                    ->where('full_name', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term))
                ->orWhereHas('group', fn (Builder $group) => $group->where('name', 'like', $term))
                ->orWhereHas('businessDetails', fn (Builder $business) => $business
                    ->where('business_name', 'like', $term)
                    ->orWhereHas('ward', fn (Builder $ward) => $ward->where('name', 'like', $term)));
        });
    }

    protected function applyListStatus(Builder $query, ?string $status): void
    {
        $status = $this->normalizeListStatus($status);

        if ($status === '') {
            return;
        }

        $query->where('status', $status);
    }

    protected function applyListSort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'amount_high' => $query->orderByDesc('requested_amount')->orderByDesc('id'),
            'amount_low' => $query->orderBy('requested_amount')->orderBy('id'),
            'track_id' => $query->orderBy('loan_track_id')->orderByDesc('id'),
            'step' => $query->orderBy('current_step')->orderByDesc('id'),
            'status' => $query->orderBy('status')->orderByDesc('id'),
            default => $query->latest()->latest('id'),
        };
    }
}
