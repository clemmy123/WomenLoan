<?php

namespace App\Http\Requests;

use App\Models\LoanGroupMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantGroupMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create loan application') ?? false;
    }

    public function rules(): array
    {
        /** @var LoanGroupMember $member */
        $member = $this->route('member');

        if ($member->is_group_leader) {
            return [
                'age' => 'required|integer|min:18|max:120',
                'sex' => 'required|in:Female,Male',
            ];
        }

        return [
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'nin' => [
                'required',
                'string',
                'max:30',
                Rule::unique('loan_group_members', 'nin')
                    ->where('loan_group_id', $member->loan_group_id)
                    ->ignore($member->id),
            ],
            'age' => 'required|integer|min:18|max:120',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'sex' => 'required|in:Female,Male',
        ];
    }
}
