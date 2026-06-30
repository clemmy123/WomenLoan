<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $loan = $this->route('loan');

        return $loan && $loan->isEditableByApplicant($this->user());
    }

    public function rules(): array
    {
        return [
            'loan_type' => 'required',
            'requested_amount' => 'required|numeric',
            'business_name' => 'required',
            'business_phone' => 'required',
            'business_email' => 'required|email',
            'business_proposal_document' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
            'business_registration_attachment' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
            'region_id' => 'nullable|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'council_id' => 'nullable|exists:councils,id',
            'ward_id' => 'nullable|exists:wards,id',
            'street_id' => 'nullable|exists:streets,id',
            'business_sector' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'guarantor_name' => 'nullable|string|max:255',
            'guarantor_phone' => 'nullable|string|max:20',
            'guarantor_nin' => 'nullable|string|max:30',
            'guarantor_relationship' => 'nullable|string|max:50',
            'guarantor_occupation' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_number' => 'nullable|string|max:255',
            'declaration' => 'accepted',
        ];
    }
}
