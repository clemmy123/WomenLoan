<?php

namespace App\Http\Requests\Applicant;

use App\Rules\TanzaniaPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $applicant = $this->route('applicant');

        return [
            'first_name' => ['required', 'string', 'max:255', 'min:2'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255', 'min:2'],
            'nin' => ['required', 'numeric', 'digits:20', Rule::unique('applicants', 'nin')->ignore($applicant->id)],
            'dob' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:255', Rule::unique('applicants', 'email')->ignore($applicant->id)],
            'phone' => ['required', 'string', new TanzaniaPhone, Rule::unique('applicants', 'phone')->ignore($applicant->id)],
            'sex' => ['required', 'string', 'in:Male,Female'],
            'marital_status' => ['nullable', 'string', 'max:20'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:streets,id'],
        ];
    }
}
