<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CdoLoanScopeService
{
    public function applyBusinessDetailsScope(Builder $query, User $user, string $relation = 'businessDetails'): void
    {
        if ($user->hasRole('cdo_ward')) {
            $query->whereHas($relation, fn (Builder $details) => $details->where('ward_id', $user->zoneable_id));

            return;
        }

        if ($user->hasRole('cdo_council')) {
            $query->whereHas($relation, fn (Builder $details) => $details->where('council_id', $user->zoneable_id));

            return;
        }

        if ($user->hasRole('cdo_region')) {
            $query->whereHas($relation, fn (Builder $details) => $details->where('region_id', $user->zoneable_id));
        }
    }

    public function canActOnLoan(User $user, Loan $loan): bool
    {
        if ($this->isGeoCdo($user) && $this->colleagueHandledLoan($user, $loan)) {
            return false;
        }

        return true;
    }

    public function handlingBadge(User $user, Loan $loan): ?array
    {
        if (! $this->isGeoCdo($user) || ! $this->colleagueHandledLoan($user, $loan)) {
            return null;
        }

        return [
            'label' => __('loans.cdo_already_handled'),
            'variant' => 'success',
        ];
    }

    public function viewOnlyMessage(User $user, Loan $loan): ?string
    {
        if (! $this->isGeoCdo($user) || ! $this->colleagueHandledLoan($user, $loan)) {
            return null;
        }

        return __('loans.cdo_view_only_colleague');
    }

    public function colleagueHandledLoan(User $user, Loan $loan): bool
    {
        $peerIds = $this->peerUserIds($user);

        if ($peerIds === []) {
            return false;
        }

        if ($loan->relationLoaded('approvalLevels')) {
            return $loan->approvalLevels->contains(
                fn ($level) => in_array($level->user_id, $peerIds, true),
            );
        }

        return $loan->approvalLevels()->whereIn('user_id', $peerIds)->exists();
    }

    protected function isGeoCdo(User $user): bool
    {
        return $user->hasRole(['cdo_ward', 'cdo_council', 'cdo_region']);
    }

    protected function peerUserIds(User $user): array
    {
        if (! $user->zoneable_type || ! $user->zoneable_id || ! $this->isGeoCdo($user)) {
            return [];
        }

        $role = $user->hasRole('cdo_ward') ? 'cdo_ward'
            : ($user->hasRole('cdo_council') ? 'cdo_council' : 'cdo_region');

        return User::role($role)
            ->where('zoneable_type', $user->zoneable_type)
            ->where('zoneable_id', $user->zoneable_id)
            ->whereKeyNot($user->id)
            ->pluck('id')
            ->all();
    }
}
