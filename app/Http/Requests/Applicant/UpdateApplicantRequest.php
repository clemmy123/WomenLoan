<?php

namespace App\Http\Requests\Applicant;

use App\Http\Requests\Concerns\EnforcesFemaleOnlySex;
use App\Http\Requests\Concerns\NormalizesIdentityFields;
use App\Models\Applicant;
use App\Rules\TanzanianNin;
use App\Rules\TanzaniaPhone;
use App\Rules\UniqueEmail;
use App\Rules\UniqueNin;
use App\Rules\UniquePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantRequest extends FormRequest
{
    use EnforcesFemaleOnlySex, NormalizesIdentityFields;

    public function authorize(): bool
    {
        $user = $this->user();
        $applicant = $this->route('applicant');

        if (! $user || ! $applicant) {
            return false;
        }

        if ($user->can('manage applicants')) {
            return true;
        }

        return $user->isApplicant()
            && $user->applicant
            && (int) $applicant->user_id === (int) $user->id;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeIdentityInput(['nin', 'phone', 'email']);
        $this->enforceFemaleOnlySex();
    }

    public function rules(): array
    {
        $applicant = $this->route('applicant');
        $userId = $applicant?->user_id;

        return [
            'first_name' => ['required', 'string', 'max:255', 'min:2'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255', 'min:2'],
            'nin' => ['required', 'string', new TanzanianNin, new UniqueNin($applicant->id)],
            'dob' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:255', new UniqueEmail($userId, $applicant->id)],
            'phone' => ['required', 'string', new TanzaniaPhone, new UniquePhone($userId, $applicant->id)],
            'sex' => ['required', 'string', 'in:Female'],
            'marital_status' => ['required', 'string', Rule::in(Applicant::MARITAL_STATUSES)],
            'preferred_loan_type' => ['required', 'string', Rule::in(Applicant::LOAN_TYPES)],
            'has_disability' => ['required', 'in:0,1'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:streets,id'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'po_box' => ['nullable', 'string', 'max:50'],
        ];
    }
}
