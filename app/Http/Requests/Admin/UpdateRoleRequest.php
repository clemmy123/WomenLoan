<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage roles');
    }

    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        $rules = [
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::in(PermissionCatalog::allPermissionNames())],
        ];

        if (! $role->isProtected()) {
            $rules['name'] = [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'name')->ignore($role->id),
                Rule::notIn(['super_admin', 'applicant']),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.regex' => __('admin.role_name_format'),
            'name.not_in' => __('admin.role_name_reserved'),
        ];
    }
}
