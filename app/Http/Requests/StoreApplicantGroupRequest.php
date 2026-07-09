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
        $this->mergeLeaderFromApplicant();
    }

    protected function mergeLeaderFromApplicant(): void
    {
        $applicant = $this->user()?->applicant;

        if (! $applicant) {
            return;
        }

        $leader = $this->input('leader', []);

        if (! is_array($leader)) {
            $leader = [];
        }

        $this->merge([
            'leader' => array_merge($leader, [
                'dob' => $applicant->dob?->format('Y-m-d'),
                'sex' => $leader['sex'] ?? $applicant->sex ?? 'Female',
            ]),
        ]);
    }

    public function rules(): array
    {
        $user = $this->user();
        $applicantId = $user?->applicant?->id;

        return [
            'name' => 'required|string|max:255|unique:loan_groups,name',
            'registration_number' => 'nullable|string|max:100|unique:loan_groups,registration_number',
            'phone' => ['nullable', 'string', new TanzaniaPhone, new UniquePhone($user?->id, $applicantId)],
            'email' => ['nullable', 'email', 'max:255', new UniqueEmail($user?->id, $applicantId)],
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
            $this->validateUniqueMemberPhonesAcrossPayload($validator);
        });
    }

    protected function validateUniqueMemberPhonesAcrossPayload(Validator $validator): void
    {
        $phones = [];
        $applicantPhone = $this->user()?->applicant?->phone;

        foreach ($this->input('members', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $phone = $row['phone'] ?? null;

            if (! filled($phone)) {
                continue;
            }

            if ($applicantPhone && $phone === $applicantPhone) {
                $validator->errors()->add(
                    "members.{$index}.phone",
                    __('groups.member_phone_same_as_leader'),
                );

                continue;
            }

            if (in_array($phone, $phones, true)) {
                $validator->errors()->add(
                    "members.{$index}.phone",
                    __('groups.member_phone_duplicate'),
                );

                continue;
            }

            $phones[] = $phone;
        }
    }
}
