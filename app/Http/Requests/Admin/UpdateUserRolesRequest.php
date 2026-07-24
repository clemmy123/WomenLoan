<?php

namespace App\Http\Requests\Admin;

use App\Support\StaffZone;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage users');
    }

    public function rules(): array
    {
        return [
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name'],
            'zone_type' => ['nullable', 'in:region,council,ward'],
            'zone_id' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            StaffZone::validateRoleZone(
                $validator,
                $this->input('roles'),
                $this->input('zone_type'),
                $this->input('zone_id')
            );
        });
    }
}
