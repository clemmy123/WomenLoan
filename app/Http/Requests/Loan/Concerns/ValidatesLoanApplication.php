<?php

namespace App\Http\Requests\Loan\Concerns;

use App\Models\Applicant;
use App\Models\Scopes\ApplicantAccess;
use App\Rules\TanzaniaPhone;
use App\Rules\TanzanianNin;
use App\Rules\UniqueNin;
use App\Rules\UniquePhone;
use App\Rules\UniqueTin;
use App\Support\IdentityNormalizer;
use App\Support\LoanWizardFieldMap;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait ValidatesLoanApplication
{
    private const DOCUMENT_MAX_KB = 1024;

    protected function prepareForValidation(): void
    {
        if ($this->filled('guarantor_first_name') && ! $this->filled('guarantor_relationship')) {
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

        if ($this->has('tin_number')) {
            $merge['tin_number'] = trim((string) $this->input('tin_number'));
        }

        if ($merge !== []) {
            $this->merge($merge);
        }

        /** @var Applicant|null $applicant */
        $applicant = $this->user()
            ?->applicant()
            ->withoutGlobalScope(ApplicantAccess::class)
            ->first();

        if ($applicant instanceof Applicant) {
            $profileMerge = [
                'has_disability' => $applicant->has_disability === null
                    ? $this->input('has_disability')
                    : ($applicant->has_disability ? '1' : '0'),
                'is_widowed' => $applicant->isWidowed() ? '1' : '0',
            ];

            $loan = $this->route('loan');

            if ($loan) {
                $profileMerge['loan_type'] = $this->input('loan_type', $loan->loan_type);
            } elseif ($applicant->preferred_loan_type) {
                $profileMerge['loan_type'] = $applicant->preferred_loan_type;
            }

            $this->merge($profileMerge);
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
            'tin_number' => [
                'required',
                'string',
                'max:50',
                new UniqueTin($updating ? $this->route('loan')?->businessDetails?->id : null),
            ],
            'has_disability' => 'required|in:0,1',
            'is_widowed' => 'required|in:0,1',
            'guarantor_first_name' => [$updating ? 'nullable' : 'required', 'string', 'max:100', 'min:2'],
            'guarantor_middle_name' => 'nullable|string|max:100',
            'guarantor_last_name' => [$updating ? 'nullable' : 'required', 'string', 'max:100', 'min:2'],
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

    protected function requiredBusinessLocationRules(): array
    {
        return [
            'region_id' => 'required|exists:regions,id',
            'district_id' => 'required|exists:districts,id',
            'council_id' => 'required|exists:councils,id',
            'ward_id' => 'required|exists:wards,id',
            'street_id' => 'required|exists:streets,id',
        ];
    }

    protected function requiredGuarantorRules(): array
    {
        return [
            'guarantor_first_name' => 'required|string|max:100|min:2',
            'guarantor_last_name' => 'required|string|max:100|min:2',
            'guarantor_phone' => ['required', 'string', new TanzaniaPhone, new UniquePhone],
            'guarantor_nin' => ['required', 'string', new TanzanianNin, new UniqueNin],
            'guarantor_sex' => 'required|string|in:Male,Female',
            'guarantor_region_id' => 'required|exists:regions,id',
            'guarantor_district_id' => 'required|exists:districts,id',
            'guarantor_council_id' => 'required|exists:councils,id',
            'guarantor_ward_id' => 'required|exists:wards,id',
            'guarantor_street_id' => 'required|exists:streets,id',
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
            'mimes:pdf',
            'max:'.self::DOCUMENT_MAX_KB,
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $firstField = collect($validator->errors()->keys())->first();
        $step = LoanWizardFieldMap::stepForField($firstField);
        $url = $this->getRedirectUrl();
        $separator = str_contains($url, '?') ? '&' : '?';

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($url.$separator.'wizard_step='.$step);
    }
}
