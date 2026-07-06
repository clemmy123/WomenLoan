<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\EnforcesFemaleOnlySex;
use App\Http\Requests\Concerns\NormalizesIdentityFields;
use App\Http\Requests\Concerns\ValidatesGroupLeadershipRole;
use App\Http\Requests\Concerns\ValidatesGroupMemberDob;
use App\Models\Applicant;
use App\Models\LoanGroupMember;
use App\Rules\TanzaniaPhone;
use App\Rules\TanzanianNin;
use App\Rules\UniqueEmail;
use App\Rules\UniqueNin;
use App\Rules\UniquePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateApplicantGroupMemberRequest extends FormRequest
{
    use EnforcesFemaleOnlySex, NormalizesIdentityFields, ValidatesGroupLeadershipRole, ValidatesGroupMemberDob;

    public function authorize(): bool
    {
        return $this->user()?->can('create loan application') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeIdentityInput(['nin', 'phone', 'email']);
        $this->normalizeLeadershipRoleInput();
        $this->enforceFemaleOnlySex();
    }

    public function rules(): array
    {
        /** @var LoanGroupMember $member */
        $member = $this->route('member');

        if ($member->is_group_leader) {
            return [
                'dob' => $this->memberDobRules(),
                'sex' => 'required|in:Female',
                'leadership_role' => $this->leadershipRoleFieldRules($member->loan_group_id, $member->id),
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
            'dob' => $this->memberDobRules(),
            'phone' => ['required', 'string', new TanzaniaPhone, new UniquePhone],
            'email' => ['nullable', 'email', 'max:255', new UniqueEmail],
            'sex' => 'required|in:Female',
            'marital_status' => ['required', 'string', Rule::in(Applicant::MARITAL_STATUSES)],
            'leadership_role' => $this->leadershipRoleFieldRules($member->loan_group_id, $member->id),
        ];
    }

    public function messages(): array
    {
        return [
            'nin.unique' => __('validation.already_used', ['attribute' => __('applicants.nin')]),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var LoanGroupMember $member */
            $member = $this->route('member');

            $this->assertExclusiveLeadershipRoleAvailable(
                $validator,
                $member->loan_group_id,
                $this->input('leadership_role'),
                $member->id,
            );
        });
    }
}
