<?php

namespace App\Services;

use App\Http\Requests\Loan\StoreLoanApplicationRequest;
use App\Models\Applicant;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanApplicationService
{
    public function __construct(
        private LoanTrackIdGenerator $trackIds,
        private DraftLoanService $drafts,
    ) {}

    public function nextTrackId(): string
    {
        return $this->trackIds->next();
    }

    public function saveDraft(Request $request, int $userId, ?string $trackId = null): string
    {
        $trackId ??= $this->nextTrackId();
        $this->drafts->save($userId, $trackId, $request, ['_token']);

        return $trackId;
    }

    public function submit(StoreLoanApplicationRequest $request, User $user, string $trackId): Loan
    {
        $applicant = $user->applicant;

        if (! $applicant) {
            throw new \RuntimeException('applicant_profile_required');
        }

        return DB::transaction(function () use ($request, $user, $trackId, $applicant) {
            $loan = Loan::create([
                'loan_track_id' => $trackId,
                'applicant_id' => $applicant->id,
                'loan_type' => $request->loan_type,
                'requested_amount' => $request->requested_amount,
                'bank_name' => $request->bank_name,
                'bank_number' => $request->bank_number,
                'user_id' => $user->id,
                'status' => 'pending',
                'current_step' => 1,
            ]);

            $loan->businessDetails()->create([
                'region_id' => $request->region_id,
                'district_id' => $request->district_id,
                'council_id' => $request->council_id,
                'ward_id' => $request->ward_id,
                'street_id' => $request->street_id,
                'business_name' => $request->business_name,
                'business_phone' => $request->business_phone,
                'business_email' => $request->business_email,
                'business_sector' => $request->business_sector,
                'business_type' => $request->business_type,
                'tin_number' => $request->tin_number,
                'business_proposal_document' => $request->file('business_proposal_document')?->store('proposals', 'public'),
                'business_registration_attachment' => $request->file('business_registration_attachment')?->store('registrations', 'public'),
            ]);

            if ($request->filled('guarantor_name')) {
                $loan->guarantors()->create([
                    'name' => $request->guarantor_name,
                    'phone' => $request->guarantor_phone,
                    'id_number' => $request->guarantor_nin,
                ]);
            }

            $this->drafts->deleteByTrackId($trackId);

            DashboardStatsService::flushForUser($user->id);

            return $loan;
        });
    }

    public function findApplicantByNin(string $nin): ?Applicant
    {
        return Applicant::where('nin', $nin)->first();
    }

    public function groupMembers(int $groupId): LoanGroup
    {
        return LoanGroup::with('applicants')->findOrFail($groupId);
    }
}
