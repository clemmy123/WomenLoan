<?php

namespace App\Http\Requests\Admin;

use App\Support\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage roles');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'name'),
                Rule::notIn(['super_admin', 'applicant']),
            ],
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::in(PermissionCatalog::allPermissionNames())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => __('admin.role_name_format'),
            'name.not_in' => __('admin.role_name_reserved'),
        ];
    }
}
