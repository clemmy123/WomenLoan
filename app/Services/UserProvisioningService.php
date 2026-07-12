<?php

namespace App\Services;

use App\Models\Concerns\HasDisplayName;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserProvisioningService
{
    public function paginated(
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = User::query()->with('roles');

        if (filled($search)) {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('middle_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('check_number', 'like', $term)
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'like', $term));
            });
        }

        if (filled($role)) {
            $query->whereHas('roles', fn ($roles) => $roles->where('name', $role));
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

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
            'must_change_password' => true,
            'temporary_password_expires_at' => null,
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
            $payload['must_change_password'] = true;
            $payload['temporary_password_expires_at'] = null;
        }

        $user->update($payload);
        $user->syncZone($validated);
        $user->syncRoles($this->sanitizeRoles($validated['roles'] ?? []));

        if ($unlockLogin) {
            app(LoginLockoutService::class)->unlock($user->fresh(), notify: true);
        }

        return $user;
    }

    public function syncRolesOnly(User $user, array $roles): User
    {
        $user->syncRoles($this->sanitizeRoles($roles));

        return $user->fresh('roles');
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
