<?php

namespace App\Services;

use App\Models\User;

class UserProvisioningService
{
    public function create(array $validated, bool $isActive = true): User
    {
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'is_active' => $isActive,
        ]);

        $user->syncZone($validated);
        $user->syncRoles($this->sanitizeRoles($validated['roles'] ?? []));

        return $user;
    }

    public function update(User $user, array $validated, bool $isActive = true): User
    {
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $isActive,
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => $validated['password']]);
        }

        $user->syncZone($validated);
        $user->syncRoles($this->sanitizeRoles($validated['roles'] ?? []));

        return $user;
    }

    public function formOptions(): array
    {
        $geo = app(GeoHierarchyService::class);

        return [
            'regions' => $geo->regions(),
            'councils' => $geo->allCouncils(),
            'wards' => $geo->allWards(),
        ];
    }

    /** @param  list<string>  $roles */
    private function sanitizeRoles(array $roles): array
    {
        $actor = auth()->user();

        if ($actor?->hasRole('super_admin')) {
            return $roles;
        }

        return array_values(array_filter(
            $roles,
            fn (string $role) => $role !== 'super_admin'
        ));
    }
}
