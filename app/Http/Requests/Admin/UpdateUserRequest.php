<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage users');
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => ['nullable', Password::defaults()],
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
            'zone_type' => 'nullable|in:region,council,ward',
            'zone_id' => 'nullable|integer',
            'is_active' => 'boolean',
        ];
    }
}
