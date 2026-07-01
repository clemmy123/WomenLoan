<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicantGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create loan application');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:loan_groups,name',
            'registration_number' => 'nullable|string|max:100|unique:loan_groups,registration_number',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'leader.age' => 'required|integer|min:18|max:120',
            'leader.sex' => 'required|in:Female,Male',
            'members' => 'required|array|min:1',
            'members.*.first_name' => 'required|string|max:100',
            'members.*.middle_name' => 'nullable|string|max:100',
            'members.*.last_name' => 'required|string|max:100',
            'members.*.nin' => 'required|string|max:30|distinct',
            'members.*.age' => 'required|integer|min:18|max:120',
            'members.*.phone' => 'required|string|max:20',
            'members.*.email' => 'nullable|email|max:255',
            'members.*.sex' => 'required|in:Female,Male',
        ];
    }
}
