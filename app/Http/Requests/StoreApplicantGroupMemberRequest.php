<?php

namespace App\Http\Requests;

use App\Services\ApplicantGroupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicantGroupMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create loan application') ?? false;
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
                'max:30',
                Rule::unique('loan_group_members', 'nin')->where('loan_group_id', $groupId),
            ],
            'age' => 'required|integer|min:18|max:120',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'sex' => 'required|in:Female,Male',
        ];
    }
}
