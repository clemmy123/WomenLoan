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
use App\Services\ApplicantGroupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicantGroupMemberRequest extends FormRequest
{
    use EnforcesFemaleOnlySex, NormalizesIdentityFields;

    public function authorize(): bool
    {
        return $this->user()?->can('create loan application') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeIdentityInput(['nin', 'phone', 'email']);
        $this->enforceFemaleOnlySex();
    }

    public function rules(): array
    {
        $group = app(ApplicantGroupService::class)->groupForUser($this->user());
        $groupId = $group?->id ?? 0;

        return [
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'nin' => [
                'required',
                'string',
                new TanzanianNin,
                new UniqueNin,
                Rule::unique('loan_group_members', 'nin')->where('loan_group_id', $groupId),
            ],
            'age' => 'required|integer|min:18|max:120',
            'phone' => ['required', 'string', new TanzaniaPhone, new UniquePhone],
            'email' => ['nullable', 'email', 'max:255', new UniqueEmail],
            'sex' => 'required|in:Female',
            'marital_status' => ['required', 'string', Rule::in(Applicant::MARITAL_STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'nin.unique' => __('validation.already_used', ['attribute' => __('applicants.nin')]),
        ];
    }
}
