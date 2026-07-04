<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\EnforcesFemaleOnlySex;
use App\Http\Requests\Concerns\NormalizesIdentityFields;
use App\Models\Applicant;
use App\Rules\TanzaniaPhone;
use App\Rules\TanzanianNin;
use App\Rules\UniqueEmail;
use App\Rules\UniqueNin;
use App\Rules\UniquePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicantGroupRequest extends FormRequest
{
    use EnforcesFemaleOnlySex, NormalizesIdentityFields;

    public function authorize(): bool
    {
        return $this->user()->can('create loan application');
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeIdentityInput(['phone', 'email']);
        $this->normalizeMemberIdentityRows();
        $this->enforceFemaleOnlySex();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:loan_groups,name',
            'registration_number' => 'nullable|string|max:100|unique:loan_groups,registration_number',
            'phone' => ['nullable', 'string', new TanzaniaPhone, new UniquePhone($this->user()?->id)],
            'email' => ['nullable', 'email', 'max:255', new UniqueEmail($this->user()?->id)],
            'leader.age' => 'required|integer|min:18|max:120',
            'leader.sex' => 'required|in:Female',
            'members' => 'required|array|min:1',
            'members.*.first_name' => 'required|string|max:100',
            'members.*.middle_name' => 'nullable|string|max:100',
            'members.*.last_name' => 'required|string|max:100',
            'members.*.nin' => ['required', 'string', new TanzanianNin, new UniqueNin, 'distinct'],
            'members.*.age' => 'required|integer|min:18|max:120',
            'members.*.phone' => ['required', 'string', new TanzaniaPhone, new UniquePhone],
            'members.*.email' => ['nullable', 'email', 'max:255', new UniqueEmail],
            'members.*.sex' => 'required|in:Female',
            'members.*.marital_status' => ['required', 'string', Rule::in(Applicant::MARITAL_STATUSES)],
        ];
    }
}
