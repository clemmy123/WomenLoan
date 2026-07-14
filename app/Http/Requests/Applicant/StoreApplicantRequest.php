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
use App\Services\ApplicantService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicantRequest extends FormRequest
{
    use EnforcesFemaleOnlySex, NormalizesIdentityFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user && ! $user->applicant && $user->isApplicant()) {
            $this->merge(app(ApplicantService::class)->registrationFieldDefaults($user));
        }

        $this->normalizeIdentityInput(['nin', 'phone', 'email']);
        $this->enforceFemaleOnlySex();
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'first_name' => ['required', 'string', 'max:255', 'min:2'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255', 'min:2'],
            'nin' => ['required', 'string', new TanzanianNin, new UniqueNin(ignoreUserId: $user?->id)],
            'dob' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:255', new UniqueEmail($user?->id)],
            'phone' => ['required', 'string', new TanzaniaPhone, new UniquePhone($user?->id)],
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
