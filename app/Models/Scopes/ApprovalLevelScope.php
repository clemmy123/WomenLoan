<?php

namespace App\Models\Scopes;

use App\Services\CdoLoanScopeService;
use App\Support\WorkflowSteps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
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

        // Chief keeps seeing loans after assigning an accountant (ready + disbursed).
        if ($user->hasRole('chief')) {
            $builder->where(function (Builder $inner) {
                $inner->where(function (Builder $awaiting) {
                    $awaiting->where('current_step', 9)
                        ->where('status', 'approved');
                })->orWhereIn('status', ['ready_for_disbursement', 'disbursed']);
            });

            return;
        }

        // Accountant keeps seeing assigned loans after putting money (disbursed).
        if ($user->hasRole('accountant')) {
            $builder->where('officer_id', $user->id)
                ->where(function (Builder $inner) {
                    $inner->where(function (Builder $ready) {
                        $ready->where('current_step', 10)
                            ->where('status', 'ready_for_disbursement');
                    })->orWhere('status', 'disbursed');
                });

            return;
        }

        if ($user->can('view all loans')) {
            return;
        }

        if ($user->hasRole(['cdo_ward', 'cdo_council', 'cdo_region'])) {
            app(CdoLoanScopeService::class)->applyBusinessDetailsScope($builder, $user);

            return;
        }

        $stepMap = WorkflowSteps::ROLE_STEP_MAP;

        foreach ($stepMap as $role => $steps) {
            if ($user->hasRole($role)) {
                $builder->whereIn('current_step', $steps);

                return;
            }
        }

        if (! $user->hasRole(['cdo_council', 'cdo_region'])) {
            $builder->whereRaw('0 = 1');
        }
    }
}
