<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApprovalLevelScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * Restricts visible loan records based on the 8-Step interactive business pipeline.
     */
    public function apply(Builder $builder, Model $model)
    {
        $user = Auth::user();

        if (!$user) {
            return $builder->whereRaw('0 = 1');
        }

        // 1. Exclude 'draft' loans for all operational/reviewing staff members
        if (!in_array($user->role, ['user', 'admin'])) {
            $builder->where('status', '!=', 'draft');
        }

        // 2. Administrative Super Pass
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return;
        }

        // 3. REGULAR APPLICANT VIEWPORT
        // Only show loans where they are the owner or part of the applying group
        if ($user->hasRole('user')) {
            $builder->where(function ($query) use ($user) {
                $query->whereHas('applicant', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->orWhereHas('applicants', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            });
            return;
        }

        $userRole = $user->getRoleNames()->first();

        // 4. JURISDICTIONAL GEOGRAPHIC FILTERS (Clean Location Traversal Chain)
        if (in_array($userRole, ['Cdo Ward', 'Cdo Council', 'Cdo Region'])) {
            $builder->whereHas('businessDetails.location', function ($q) use ($user, $userRole) {
                if ($userRole === 'Cdo Ward') {
                    $q->where('ward_id', $user->zoneable_id);
                }
                if ($userRole === 'Cdo Council') {
                    // FIXED: Table column completely synchronized to council_id
                    $q->where('council_id', $user->zoneable_id);
                }
                if ($userRole === 'Cdo Region') {
                    $q->where('region_id', $user->zoneable_id);
                }
            });
        }

        // 5. THE 8-STEP APPROVAL WORKFLOW ROUTING ENGINE
        $builder->whereHas('approvalStatus', function ($q) use ($user, $userRole) {
            
            // --- Step 1 & 3: Ward Level Processing ---
            if ($userRole === 'Cdo Ward') {
                $q->whereIn('current_step', [1, 3]);
                return;
            }

            // --- Step 2 & 4: Ministry Verifier 1 ---
            if ($userRole === 'Ministry Verifier 1') {
                $q->whereIn('current_step', [2, 4]);
                return;
            }

            // --- Step 5: Ministry Verifier 2 ---
            if ($userRole === 'Ministry Verifier 2') {
                $q->where('current_step', 5);
                return;
            }

            // --- Step 6: Ministry Verifier 3 ---
            if ($userRole === 'Ministry Verifier 3') {
                $q->where('current_step', 6);
                return;
            }

            // --- Step 7: Ministry Verifier 4 (Final Approver) ---
            // Handles final sign-off and checks the generation of the 16% repayment ledger
            if ($userRole === 'Ministry Verifier 4') {
                $q->where('current_step', 7);
                return;
            }

            // --- Step 8: Ministry Chief Accountant (Disbursement) ---
            // Only sees completed approvals ready for physical cash/funds release execution
            if ($userRole === 'Ministry Chief Accountant' || $userRole === 'Ca') {
                $q->where('current_step', 8)
                  ->where('status', 'Completed');
                return;
            }

            // Fallback safety shield: If a role doesn't have an active step step assignment, block visibility
            Log::warning("User role {$userRole} attempted to access pipeline without assigned step clearance.");
            $q->whereRaw('0 = 1');
        });
    }
}