<?php

namespace App\Models\Scopes;

use App\Models\Applicant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApplicantAccess implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * Cascades down permissions based on regional, council, or ward oversight tiers.
     */
    public function apply(Builder $builder, Model $model)
    {
        $user = Auth::user();

        // Safety catch: If no user session exists, completely deny data access
        if (!$user) {
            $builder->whereRaw('0 = 1');
            return;
        }

        Log::info("Applying ApplicantAccess scope for user ID: {$user->id} on model: " . get_class($model));

        // --- 1. CDO REGION OVERSIGHT LEVEL ROLE ---
        // Displays all loan data across ALL councils and wards under their entire specific region
        if ($user->hasRole('Cdo Region')) {
            $builder->whereHas('loan.businessDetails.location', function ($q) use ($user) {
                // Filters down by matching the region_id to the user's polymorphic zoneable_id
                $q->where('region_id', $user->zoneable_id);
            });
            return;
        }

        // --- 2. CDO COUNCIL APPROVAL LEVEL ROLE ---
        // Shows only loans and records matching this specific Council administrative zone
        if ($user->hasRole('Cdo Council')) {
            $builder->whereHas('loan.businessDetails.location', function ($q) use ($user) {
                $q->where('council_id', $user->zoneable_id);
            });
            return;
        }

        // --- 3. CDO WARD APPROVAL LEVEL ROLE ---
        // Shows only loans and records operating within their designated Ward zone
        if ($user->hasRole('Cdo Ward')) {
            $builder->whereHas('loan.businessDetails.location', function ($q) use ($user) {
                $q->where('ward_id', $user->zoneable_id);
            });
            return;
        }

        // --- 4. REGULAR APPLICANT/USER RESTRICTIONS ---
        // Standard loan applicants can only see their own records or their group's records
        if ($user->role === 'user') {
            $applicant = Applicant::where('user_id', $user->id)->first();

            if (!$applicant) {
                $builder->whereRaw('0 = 1'); // Deny reading access immediately
                return;
            }

            // Instance filtering context rules
            if ($model instanceof \App\Models\LoanPayment) {
                $builder->whereHas('loan', function ($q) use ($applicant) {
                    $q->where('applicant_id', $applicant->id)
                        ->orWhereHas('group.applicants', function ($q2) use ($applicant) {
                            $q2->where('applicant_id', $applicant->id);
                        });
                });
            }

            if ($model instanceof \App\Models\LoanGroup) {
                $builder->whereHas('applicants', function ($q) use ($applicant) {
                    $q->where('applicant_id', $applicant->id);
                });
            }
        }
    }
}