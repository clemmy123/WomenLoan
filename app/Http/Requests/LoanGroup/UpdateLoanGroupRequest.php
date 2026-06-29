<?php

namespace App\Http\Requests\LoanGroup;

use App\Rules\ApplicantNotInAnyGroup;
use App\Rules\TanzaniaPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $group = $this->route('loan_group');

        return [
            'name' => ['required', Rule::unique('loan_groups', 'name')->ignore($group->id)],
            'registration_number' => ['nullable', Rule::unique('loan_groups', 'registration_number')->ignore($group->id)],
            'phone' => ['nullable', new TanzaniaPhone],
            'email' => ['nullable', 'email', 'max:255'],
            'applicants' => ['nullable', 'array'],
            'applicants.*' => ['integer', 'exists:applicants,id', new ApplicantNotInAnyGroup($group->id)],
        ];
    }
}
