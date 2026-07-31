<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesStaffRoleSelection;
use App\Support\StaffAdminScope;
use App\Support\StaffZone;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRolesRequest extends FormRequest
{
    use NormalizesStaffRoleSelection;

    public function authorize(): bool
    {
        $target = $this->route('user');

        return $this->user()->can('manage users')
            && StaffAdminScope::canManage($this->user(), $target);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStaffRoleSelection();
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'exists:roles,name'],
            'roles' => ['required', 'array', 'size:1'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
            'zone_type' => ['nullable', 'in:region,council,ward'],
            'zone_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => __('admin.roles_required'),
            'roles.required' => __('admin.roles_required'),
            'roles.size' => __('admin.roles_required'),
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

            $actor = $this->user();
            StaffAdminScope::validateAssignableRoles($validator, $actor, $this->input('roles'));
            StaffAdminScope::validateZoneWithinScope(
                $validator,
                $actor,
                $this->input('roles'),
                $this->input('zone_type'),
                $this->input('zone_id')
            );
        });
    }
}
