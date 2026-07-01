<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Support\WorkflowSteps;
use Illuminate\Support\Facades\Auth;

class ApprovalLevelScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user) {
            $builder->whereRaw('0 = 1');

            return;
        }

        if ($user->hasRole(['admin', 'super_admin'])) {
            return;
        }

        if ($user->hasRole('applicant')) {
            $builder->where('user_id', $user->id);

            return;
        }

        if ($user->can('view all loans')) {
            return;
        }

        if ($user->hasRole(['cdo_ward', 'cdo_council', 'cdo_region'])) {
            $builder->whereHas('businessDetails', function ($q) use ($user) {
                if ($user->hasRole('cdo_ward')) {
                    $q->where('ward_id', $user->zoneable_id);
                } elseif ($user->hasRole('cdo_council')) {
                    $q->where('council_id', $user->zoneable_id);
                } elseif ($user->hasRole('cdo_region')) {
                    $q->where('region_id', $user->zoneable_id);
                }
            });

            if ($user->hasRole('cdo_ward')) {
                $builder->whereIn('current_step', WorkflowSteps::ROLE_STEP_MAP['cdo_ward']);
            }

            return;
        }

        $stepMap = WorkflowSteps::ROLE_STEP_MAP;

        foreach ($stepMap as $role => $steps) {
            if ($user->hasRole($role)) {
                $builder->whereIn('current_step', $steps);
                if ($role === 'accountant') {
                    $builder->where('officer_id', $user->id);
                }

                return;
            }
        }

        if (! $user->hasRole(['cdo_council', 'cdo_region'])) {
            $builder->whereRaw('0 = 1');
        }
    }
}
