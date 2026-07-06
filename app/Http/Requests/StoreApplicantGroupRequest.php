<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\EnforcesFemaleOnlySex;
use App\Http\Requests\Concerns\NormalizesIdentityFields;
use App\Http\Requests\Concerns\ValidatesGroupLeadershipRole;
use App\Http\Requests\Concerns\ValidatesGroupMemberDob;
use App\Models\Applicant;
use App\Rules\TanzaniaPhone;
use App\Rules\TanzanianNin;
use App\Rules\UniqueEmail;
use App\Rules\UniqueNin;
use App\Rules\UniquePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreApplicantGroupRequest extends FormRequest
{
    use EnforcesFemaleOnlySex, NormalizesIdentityFields, ValidatesGroupLeadershipRole, ValidatesGroupMemberDob;

    public function authorize(): bool
    {
        return $this->user()->can('create loan application');
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeIdentityInput(['phone', 'email']);
        $this->normalizeMemberIdentityRows();
        $this->normalizeLeadershipRoleInput();
        $this->enforceFemaleOnlySex();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:loan_groups,name',
            'registration_number' => 'nullable|string|max:100|unique:loan_groups,registration_number',
            'phone' => ['nullable', 'string', new TanzaniaPhone, new UniquePhone($this->user()?->id)],
            'email' => ['nullable', 'email', 'max:255', new UniqueEmail($this->user()?->id)],
            'leader.dob' => $this->memberDobRules(),
            'leader.sex' => 'required|in:Female',
            'leader.leadership_role' => $this->leadershipRoleFieldRules(),
            'members' => 'required|array|min:1',
            'members.*.first_name' => 'required|string|max:100',
            'members.*.middle_name' => 'nullable|string|max:100',
            'members.*.last_name' => 'required|string|max:100',
            'members.*.nin' => ['required', 'string', new TanzanianNin, new UniqueNin, 'distinct'],
            'members.*.dob' => $this->memberDobRules(),
            'members.*.phone' => ['required', 'string', new TanzaniaPhone, new UniquePhone],
            'members.*.email' => ['nullable', 'email', 'max:255', new UniqueEmail],
            'members.*.sex' => 'required|in:Female',
            'members.*.marital_status' => ['required', 'string', Rule::in(Applicant::MARITAL_STATUSES)],
            'members.*.leadership_role' => $this->leadershipRoleFieldRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateUniqueLeadershipRolesAcrossPayload($validator);
        });
    }
}
