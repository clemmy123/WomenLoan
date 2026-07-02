<?php

namespace App\Services;

use App\Http\Requests\Loan\StoreLoanApplicationRequest;
use App\Http\Requests\Loan\UpdateLoanApplicationRequest;
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
        private ApplicantGroupService $groups,
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
            $loanGroupId = $request->loan_type === 'group'
                ? $this->resolveGroupIdForUser($user, $request->loan_group_id)
                : null;

            $loan = Loan::create([
                'loan_track_id' => $trackId,
                'applicant_id' => $applicant->id,
                'loan_group_id' => $loanGroupId,
                'loan_type' => $request->loan_type,
                'has_disability' => $request->boolean('has_disability'),
                'is_widowed' => $request->boolean('is_widowed'),
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
                'application_letter' => $request->file('application_letter')?->store('application-letters', 'public'),
                'bank_statement' => $request->file('bank_statement')?->store('bank-statements', 'public'),
                'group_constitution' => $request->file('group_constitution')?->store('group-documents', 'public'),
                'group_muhtasari' => $request->file('group_muhtasari')?->store('group-documents', 'public'),
                'group_certificate' => $request->file('group_certificate')?->store('group-documents', 'public'),
            ]);

            if ($request->filled('guarantor_name')) {
                $loan->guarantors()->create([
                    'applicant_id' => $applicant->id,
                    'name' => $request->guarantor_name,
                    'phone' => $request->guarantor_phone,
                    'id_number' => $request->guarantor_nin,
                    'relationship' => $this->resolveGuarantorRelationship($request),
                    'occupation' => $request->guarantor_occupation,
                ]);
            }

            $this->drafts->deleteByTrackId($trackId);

            DashboardStatsService::flushForUser($user->id);

            return $loan;
        });
    }

    public function formDataFromLoan(Loan $loan): array
    {
        $loan->loadMissing(['businessDetails', 'guarantors']);
        $business = $loan->businessDetails;
        $guarantor = $loan->guarantors->first();

        return [
            'loan_type' => $loan->loan_type,
            'loan_group_id' => $loan->loan_group_id,
            'region_id' => $business?->region_id,
            'district_id' => $business?->district_id,
            'council_id' => $business?->council_id,
            'ward_id' => $business?->ward_id,
            'street_id' => $business?->street_id,
            'business_name' => $business?->business_name,
            'business_phone' => $business?->business_phone,
            'business_email' => $business?->business_email,
            'business_sector' => $business?->business_sector,
            'business_type' => $business?->business_type,
            'tin_number' => $business?->tin_number,
            'guarantor_name' => $guarantor?->name,
            'guarantor_phone' => $guarantor?->phone,
            'guarantor_nin' => $guarantor?->id_number,
            'guarantor_relationship' => $guarantor?->relationship,
            'guarantor_occupation' => $guarantor?->occupation,
            'requested_amount' => $loan->requested_amount,
            'bank_name' => $loan->bank_name,
            'bank_number' => $loan->bank_number,
            'has_disability' => $loan->has_disability === null ? null : ($loan->has_disability ? '1' : '0'),
            'is_widowed' => $loan->is_widowed === null ? null : ($loan->is_widowed ? '1' : '0'),
            'declaration' => true,
        ];
    }

    public function update(UpdateLoanApplicationRequest $request, Loan $loan): Loan
    {
        return DB::transaction(function () use ($request, $loan) {
            $loanGroupId = $request->loan_type === 'group'
                ? $this->resolveGroupIdForUser($loan->user, $request->loan_group_id)
                : null;

            $loan->update([
                'loan_type' => $request->loan_type,
                'loan_group_id' => $loanGroupId,
                'has_disability' => $request->boolean('has_disability'),
                'is_widowed' => $request->boolean('is_widowed'),
                'requested_amount' => $request->requested_amount,
                'bank_name' => $request->bank_name,
                'bank_number' => $request->bank_number,
            ]);

            $businessData = [
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
            ];

            if ($request->hasFile('business_proposal_document')) {
                $businessData['business_proposal_document'] = $request->file('business_proposal_document')
                    ->store('proposals', 'public');
            }

            if ($request->hasFile('business_registration_attachment')) {
                $businessData['business_registration_attachment'] = $request->file('business_registration_attachment')
                    ->store('registrations', 'public');
            }

            if ($request->hasFile('application_letter')) {
                $businessData['application_letter'] = $request->file('application_letter')
                    ->store('application-letters', 'public');
            }

            if ($request->hasFile('bank_statement')) {
                $businessData['bank_statement'] = $request->file('bank_statement')
                    ->store('bank-statements', 'public');
            }

            if ($request->hasFile('group_constitution')) {
                $businessData['group_constitution'] = $request->file('group_constitution')
                    ->store('group-documents', 'public');
            }

            if ($request->hasFile('group_muhtasari')) {
                $businessData['group_muhtasari'] = $request->file('group_muhtasari')
                    ->store('group-documents', 'public');
            }

            if ($request->hasFile('group_certificate')) {
                $businessData['group_certificate'] = $request->file('group_certificate')
                    ->store('group-documents', 'public');
            }

            $loan->businessDetails()->updateOrCreate(
                ['loan_id' => $loan->id],
                $businessData,
            );

            if ($request->filled('guarantor_name')) {
                $guarantorData = [
                    'applicant_id' => $loan->applicant_id,
                    'name' => $request->guarantor_name,
                    'phone' => $request->guarantor_phone,
                    'id_number' => $request->guarantor_nin,
                    'relationship' => $this->resolveGuarantorRelationship($request),
                    'occupation' => $request->guarantor_occupation,
                ];

                $existing = $loan->guarantors()->first();

                if ($existing) {
                    $existing->update($guarantorData);
                } else {
                    $loan->guarantors()->create($guarantorData);
                }
            } else {
                $loan->guarantors()->delete();
            }

            DashboardStatsService::flushForUser($loan->user_id);

            return $loan->fresh();
        });
    }

    public function findApplicantByNin(string $nin): ?Applicant
    {
        return Applicant::where('nin', $nin)->first();
    }

    public function groupMembers(int $groupId): LoanGroup
    {
        return LoanGroup::with('members')->findOrFail($groupId);
    }

    protected function resolveGroupIdForUser(User $user, ?int $groupId): int
    {
        $group = $this->groups->groupForUser($user);

        if (! $group || (int) $groupId !== (int) $group->id) {
            throw new \RuntimeException('invalid_loan_group');
        }

        return $group->id;
    }

    private function resolveGuarantorRelationship(Request $request): string
    {
        $relationship = trim((string) $request->input('guarantor_relationship', ''));

        return $relationship !== '' ? $relationship : 'Other';
    }
}
