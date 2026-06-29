<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    public function create(array $validated): Role
    {
        return DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($validated['permissions'] ?? []);
            $this->flushPermissionCache();

            return $role;
        });
    }

    public function update(Role $role, array $validated): Role
    {
        if ($role->hasLockedPermissions()) {
            throw ValidationException::withMessages([
                'error' => __('messages.cannot_edit_super_admin'),
            ]);
        }

        return DB::transaction(function () use ($role, $validated) {
            if (! $role->isProtected() && isset($validated['name'])) {
                $role->update(['name' => $validated['name']]);
            }

            $role->syncPermissions($validated['permissions'] ?? []);
            $this->flushPermissionCache();

            return $role->fresh(['permissions']);
        });
    }

    public function delete(Role $role): void
    {
        if ($role->isProtected()) {
            throw ValidationException::withMessages([
                'error' => __('messages.cannot_delete_system_role'),
            ]);
        }

        if ($role->users()->count() > 0) {
            throw ValidationException::withMessages([
                'error' => __('messages.cannot_delete_role_with_users'),
            ]);
        }

        $role->delete();
        $this->flushPermissionCache();
    }

    private function flushPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
