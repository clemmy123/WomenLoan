<?php

namespace App\Models\Scopes;

use App\Models\Applicant;
use App\Models\LoanGroup;
use App\Models\LoanPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class ApplicantAccess implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user) {
            $builder->whereRaw('0 = 1');

            return;
        }

        if ($user->hasRole(['admin', 'super_admin', 'cdo_ministry', 'assistant_director', 'director', 'km', 'chief', 'accountant'])) {
            return;
        }

        if ($user->can('manage applicants')) {
            return;
        }

        if ($user->hasRole('cdo_region')) {
            $this->filterByGeo($builder, $model, 'region_id', $user->zoneable_id);

            return;
        }

        if ($user->hasRole('cdo_council')) {
            $this->filterByGeo($builder, $model, 'council_id', $user->zoneable_id);

            return;
        }

        if ($user->hasRole('cdo_ward')) {
            $this->filterByGeo($builder, $model, 'ward_id', $user->zoneable_id);

            return;
        }

        if ($user->hasRole('applicant')) {
            if ($model instanceof Applicant) {
                $builder->where('user_id', $user->id);

                return;
            }

            $applicantId = $user->relationLoaded('applicant')
                ? $user->applicant?->id
                : $user->applicant()->withoutGlobalScope(self::class)->value('id');

            if (! $applicantId) {
                $builder->whereRaw('0 = 1');

                return;
            }

            if ($model instanceof LoanPayment) {
                $builder->whereHas('loan', fn ($q) => $q->where('applicant_id', $applicantId));
            } elseif ($model instanceof LoanGroup) {
                $builder->whereHas('applicants', fn ($q) => $q->where('applicant_id', $applicantId));
            }

            return;
        }

        $builder->whereRaw('0 = 1');
    }

    protected function filterByGeo(Builder $builder, Model $model, string $column, ?int $zoneId): void
    {
        if (! $zoneId) {
            $builder->whereRaw('0 = 1');

            return;
        }

        if ($model instanceof Applicant) {
            $builder->whereHas('location.ward.council.district', function ($q) use ($column, $zoneId) {
                if ($column === 'ward_id') {
                    $q->where('wards.id', $zoneId);
                } elseif ($column === 'council_id') {
                    $q->where('councils.id', $zoneId);
                } else {
                    $q->where('region_id', $zoneId);
                }
            });
        } elseif ($model instanceof LoanPayment) {
            $builder->whereHas('loan.businessDetails', fn ($q) => $q->where($column, $zoneId));
        }
    }
}
