<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Scopes\ApplicantAccess;
use App\Models\Scopes\ApprovalLevelScope;
use App\Models\User;
use App\Services\Concerns\FiltersLoanLists;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LoanQueryService
{
    use FiltersLoanLists;

    public function paginatedIndex(
        ?string $search = null,
        ?string $sort = null,
        ?string $status = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $sort = $this->normalizeListSort($sort);
        $status = $this->normalizeListStatus($status);

        $query = Loan::query()
            ->select([
                'id', 'loan_track_id', 'loan_type', 'loan_group_id', 'applicant_id',
                'requested_amount', 'status', 'current_step', 'user_id', 'officer_id', 'created_at',
            ])
            ->with([
                'applicant:id,full_name,first_name,last_name',
                'group:id,name',
                'businessDetails:loan_id,ward_id,council_id,business_name',
                'businessDetails.ward:id,name',
                'approvalLevels:id,loan_id,user_id',
            ]);

        $this->applyListSearch($query, $search);
        $this->applyListStatus($query, $status);
        $this->applyActionableFirst($query);
        $this->applyListSort($query, $sort);

        return $query
            ->paginate($perPage)
            ->withQueryString();
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
            'applicant' => fn ($query) => $query
                ->withoutGlobalScope(ApplicantAccess::class)
                ->with('location.ward.council.district.region'),
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
            'group.applicants' => fn ($query) => $query->withoutGlobalScope(ApplicantAccess::class),
        ]);
    }

    public function accountants(): Collection
    {
        return User::role('accountant')->get(['id', 'name', 'email']);
    }

    public function userHasLoanApplication(User $user): bool
    {
        if (! $user->hasRole('applicant')) {
            return false;
        }

        $applicantId = $user->applicant?->id;

        return Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where(function ($query) use ($user, $applicantId) {
                $query->where('user_id', $user->id);

                if ($applicantId) {
                    $query->orWhere('applicant_id', $applicantId);
                }
            })
            ->exists();
    }
}
