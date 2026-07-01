<?php

namespace App\Http\Requests\Loan\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesLoanApplication
{
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
            'business_phone' => 'required',
            'business_email' => 'required|email',
            'business_proposal_document' => [$updating ? 'nullable' : 'required', 'file', 'mimes:pdf,docx,doc', 'max:5120'],
            'business_registration_attachment' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
            'application_letter' => [
                Rule::requiredIf($needsLetterOrStatement),
                'nullable',
                'file',
                'mimes:pdf,docx,doc',
                'max:5120',
            ],
            'bank_statement' => [
                Rule::requiredIf($needsLetterOrStatement),
                'nullable',
                'file',
                'mimes:pdf,docx,doc',
                'max:5120',
            ],
            'group_constitution' => [
                Rule::requiredIf($isGroup),
                'nullable',
                'file',
                'mimes:pdf,docx,doc',
                'max:5120',
            ],
            'group_muhtasari' => [
                Rule::requiredIf($isGroup),
                'nullable',
                'file',
                'mimes:pdf,docx,doc',
                'max:5120',
            ],
            'group_certificate' => [
                Rule::requiredIf($isGroup),
                'nullable',
                'file',
                'mimes:pdf,docx,doc',
                'max:5120',
            ],
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
            'guarantor_name' => 'nullable|string|max:255',
            'guarantor_phone' => 'nullable|string|max:20',
            'guarantor_nin' => 'nullable|string|max:30',
            'guarantor_relationship' => 'nullable|string|max:50',
            'guarantor_occupation' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_number' => 'nullable|string|max:255',
        ];
    }
}
