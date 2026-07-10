<?php

namespace App\Services;

use App\Models\Concerns\HasDisplayName;
use App\Models\User;

class UserProvisioningService
{
    public function create(array $validated, bool $isActive = true): User
    {
        $user = User::create([
            'check_number' => $validated['check_number'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'name' => HasDisplayName::buildFullName(
                $validated['first_name'],
                $validated['middle_name'] ?? null,
                $validated['last_name']
            ),
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'is_active' => $isActive,
        ]);

        $user->syncZone($validated);
        $user->syncRoles($this->sanitizeRoles($validated['roles'] ?? []));

        return $user;
    }

    public function update(User $user, array $validated, bool $isActive = true, bool $unlockLogin = false): User
    {
        $payload = [
            'check_number' => $validated['check_number'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'name' => HasDisplayName::buildFullName(
                $validated['first_name'],
                $validated['middle_name'] ?? null,
                $validated['last_name']
            ),
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_active' => $isActive,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);
        $user->syncZone($validated);
        $user->syncRoles($this->sanitizeRoles($validated['roles'] ?? []));

        if ($unlockLogin) {
            app(LoginLockoutService::class)->unlock($user->fresh(), notify: true);
        }

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
