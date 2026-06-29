<?php

namespace App\Http\Requests\LoanGroup;

use App\Rules\ApplicantNotInAnyGroup;
use App\Rules\TanzaniaPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('loan_groups', 'name')],
            'registration_number' => ['nullable', Rule::unique('loan_groups', 'registration_number')],
            'phone' => ['nullable', new TanzaniaPhone],
            'email' => ['nullable', 'email', 'max:255'],
            'applicants' => ['nullable', 'array'],
            'applicants.*' => ['integer', 'exists:applicants,id', new ApplicantNotInAnyGroup],
        ];
    }
}
