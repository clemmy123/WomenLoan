<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\EnforcesFemaleOnlySex;
use App\Http\Requests\Concerns\NormalizesIdentityFields;
use App\Models\Applicant;
use App\Models\LoanGroupMember;
use App\Rules\TanzaniaPhone;
use App\Rules\TanzanianNin;
use App\Rules\UniqueEmail;
use App\Rules\UniqueNin;
use App\Rules\UniquePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantGroupMemberRequest extends FormRequest
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
        /** @var LoanGroupMember $member */
        $member = $this->route('member');

        if ($member->is_group_leader) {
            return [
                'age' => 'required|integer|min:18|max:120',
                'sex' => 'required|in:Female',
            ];
        }

        return [
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'nin' => [
                'required',
                'string',
                new TanzanianNin,
                new UniqueNin(ignoreGroupMemberId: $member->id),
                Rule::unique('loan_group_members', 'nin')
                    ->where('loan_group_id', $member->loan_group_id)
                    ->ignore($member->id),
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
