<?php

namespace App\Http\Requests\Loan\Concerns;

use App\Rules\TanzaniaPhone;
use App\Rules\TanzanianNin;
use App\Rules\UniqueNin;
use App\Rules\UniquePhone;
use App\Support\IdentityNormalizer;
use Illuminate\Validation\Rule;

trait ValidatesLoanApplication
{
    private const DOCUMENT_MAX_KB = 1024;

    protected function prepareForValidation(): void
    {
        if ($this->filled('guarantor_name') && ! $this->filled('guarantor_relationship')) {
            $this->merge(['guarantor_relationship' => 'Other']);
        }

        $merge = [];

        if ($this->has('guarantor_nin')) {
            $merge['guarantor_nin'] = IdentityNormalizer::normalizeNin($this->input('guarantor_nin'));
        }

        if ($this->has('guarantor_phone')) {
            $merge['guarantor_phone'] = IdentityNormalizer::normalizePhone($this->input('guarantor_phone'));
        }

        if ($this->has('business_phone')) {
            $merge['business_phone'] = IdentityNormalizer::normalizePhone($this->input('business_phone'));
        }

        if ($this->has('requested_amount')) {
            $merge['requested_amount'] = IdentityNormalizer::normalizeAmount($this->input('requested_amount'));
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    protected function isGroupLoanType(): bool
    {
        $loan = $this->route('loan');

        return $this->input('loan_type', $loan?->loan_type) === 'group';
    }

    protected function isGroupDocument(string $column): bool
    {
        return in_array($column, [
            'group_constitution',
            'group_muhtasari',
            'group_certificate',
        ], true);
    }

    protected function loanApplicationRules(bool $updating = false): array
    {
        $loanType = fn () => $this->input('loan_type');
        $isIndividual = fn () => ! $updating && $loanType() === 'individual';
        $isGroup = fn () => ! $updating && $loanType() === 'group';
        $needsLetterOrStatement = fn () => ! $updating && in_array($loanType(), ['individual', 'group'], true);

        return [
            'loan_type' => 'required|in:individual,group',
            'loan_group_id' => [
                Rule::requiredIf(fn () => $loanType() === 'group'),
                'nullable',
                'exists:loan_groups,id',
            ],
            'requested_amount' => 'required|numeric',
            'business_name' => 'required',
            'business_phone' => ['required', 'string', new TanzaniaPhone],
            'business_email' => 'required|email',
            'business_proposal_document' => $this->documentRules(
                $updating,
                'business_proposal_document',
            ),
            'business_registration_attachment' => $this->documentRules(
                $updating,
                'business_registration_attachment',
            ),
            'proof_address_attachment' => $this->documentRules(
                $updating,
                'proof_address_attachment',
            ),
            'application_letter' => $this->documentRules(
                $updating,
                'application_letter',
                requiredOnCreate: $needsLetterOrStatement,
            ),
            'bank_statement' => $this->documentRules(
                $updating,
                'bank_statement',
                requiredOnCreate: $needsLetterOrStatement,
            ),
            'group_constitution' => $this->documentRules(
                $updating,
                'group_constitution',
                requiredOnCreate: $isGroup,
            ),
            'group_muhtasari' => $this->documentRules(
                $updating,
                'group_muhtasari',
                requiredOnCreate: $isGroup,
            ),
            'group_certificate' => $this->documentRules(
                $updating,
                'group_certificate',
                requiredOnCreate: $isGroup,
            ),
            'region_id' => 'nullable|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'council_id' => 'nullable|exists:councils,id',
            'ward_id' => 'nullable|exists:wards,id',
            'street_id' => 'nullable|exists:streets,id',
            'business_sector' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'has_disability' => 'required|in:0,1',
            'is_widowed' => 'required|in:0,1',
            'guarantor_name' => [$updating ? 'nullable' : 'required', 'string', 'max:255'],
            'guarantor_phone' => ['nullable', 'string', new TanzaniaPhone, new UniquePhone],
            'guarantor_nin' => ['nullable', 'string', new TanzanianNin, new UniqueNin],
            'guarantor_relationship' => 'nullable|string|max:50',
            'guarantor_occupation' => 'nullable|string|max:255',
            'guarantor_sex' => [$updating ? 'nullable' : 'required', 'string', 'in:Male,Female'],
            'guarantor_region_id' => [$updating ? 'nullable' : 'required', 'exists:regions,id'],
            'guarantor_district_id' => [$updating ? 'nullable' : 'required', 'exists:districts,id'],
            'guarantor_council_id' => [$updating ? 'nullable' : 'required', 'exists:councils,id'],
            'guarantor_ward_id' => [$updating ? 'nullable' : 'required', 'exists:wards,id'],
            'guarantor_street_id' => [$updating ? 'nullable' : 'required', 'exists:streets,id'],
            'guarantor_letter' => $this->documentRules(
                $updating,
                'guarantor_letter',
                requiredOnCreate: fn () => true,
                relation: 'guarantors',
            ),
            'bank_name' => ['nullable', 'string', Rule::in(config('banks.names', []))],
            'bank_number' => 'nullable|string|max:255',
        ];
    }

    /**
     * @param  \Closure(): bool|null  $requiredOnCreate
     */
    private function documentRules(
        bool $updating,
        string $column,
        ?\Closure $requiredOnCreate = null,
        string $relation = 'businessDetails',
    ): array {
        $requiredOnCreate ??= fn () => true;

        return [
            Rule::requiredIf(function () use ($updating, $column, $requiredOnCreate, $relation) {
                if ($this->isGroupDocument($column) && ! $this->isGroupLoanType()) {
                    return false;
                }

                if ($updating) {
                    $loan = $this->route('loan');

                    if ($relation === 'guarantors') {
                        return ! $loan?->guarantors()->first()?->guarantor_letter;
                    }

                    return ! $loan?->{$relation}?->{$column};
                }

                return $requiredOnCreate();
            }),
            'nullable',
            'file',
            'mimes:pdf,docx,doc',
            'max:'.self::DOCUMENT_MAX_KB,
        ];
    }
}
