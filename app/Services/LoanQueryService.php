<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LoanQueryService
{
    public function paginatedIndex(int $perPage = 15): LengthAwarePaginator
    {
        return Loan::query()
            ->select([
                'id', 'loan_track_id', 'loan_type', 'requested_amount',
                'status', 'current_step', 'user_id', 'created_at',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function paginatedForReports(int $perPage = 15): LengthAwarePaginator
    {
        return Loan::query()
            ->select(['id', 'loan_track_id', 'applicant_id', 'requested_amount', 'current_step', 'status', 'created_at'])
            ->with([
                'applicant:id,full_name,first_name,last_name',
                'businessDetails:loan_id,region_id',
                'businessDetails.region:id,name',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function loadForShow(Loan $loan): Loan
    {
        return $loan->load([
            'applicant.location.ward.council.district.region',
            'businessDetails.region',
            'businessDetails.district',
            'businessDetails.council',
            'businessDetails.ward',
            'businessDetails.street',
            'approvalLevels.user',
            'guarantors.region',
            'guarantors.district',
            'guarantors.council',
            'guarantors.ward',
            'guarantors.street',
            'officer',
            'group',
            'group.members',
        ]);
    }

    public function accountants(): Collection
    {
        return User::role('accountant')->get(['id', 'name', 'email']);
    }

    public function userHasActiveLoan(User $user): bool
    {
        if (! $user->hasRole('applicant')) {
            return false;
        }

        $applicantId = $user->applicant?->id;

        return Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->active()
            ->where(function ($query) use ($user, $applicantId) {
                $query->where('user_id', $user->id);

                if ($applicantId) {
                    $query->orWhere('applicant_id', $applicantId);
                }
            })
            ->exists();
    }
}
