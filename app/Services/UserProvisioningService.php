<?php

namespace App\Services;

use App\Models\Concerns\HasDisplayName;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserProvisioningService
{
    public function paginated(
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->staffUsersQuery($search, $role, $status)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, array{check_number: string, name: string, email: string, phone: string, roles: string, status: string}>
     */
    public function exportRows(
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
    ): Collection {
        return $this->staffUsersQuery($search, $role, $status)
            ->get()
            ->map(fn (User $user) => [
                'check_number' => $user->check_number ?: '—',
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?: '—',
                'roles' => $user->roles->map(fn ($role) => role_label($role->name))->implode(', '),
                'status' => $user->is_active ? __('common.active') : __('common.inactive'),
            ]);
    }

    public function exportFilename(string $extension): string
    {
        return 'wdf-users-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    protected function staffUsersQuery(
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
    ): Builder {
        $query = User::query()->with('roles');

        // Admin Users list is staff-only (cdo_ward and above). Applicants live under Applicants.
        $query->whereHas(
            'roles',
            fn ($roles) => $roles->where('name', '!=', AdminDashboardService::APPLICANT_ROLE)
        );

        if (filled($search)) {
            $term = '%'.addcslashes($search, '%_\\').'%';
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

        return $query->orderBy('name')->orderBy('id');
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

        $wasActive = (bool) $user->is_active;
        $user->update($payload);

        if ($wasActive && ! $isActive) {
            $user->forceFill([
                'deactivation_reason' => trim((string) ($validated['deactivation_reason'] ?? '')),
                'deactivated_at' => now(),
                'deactivated_by' => auth()->id(),
            ])->save();
        } elseif (! $wasActive && $isActive) {
            $user->forceFill([
                'deactivation_reason' => null,
                'deactivated_at' => null,
                'deactivated_by' => null,
            ])->save();
        }

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

    public function syncRolesAndZone(User $user, array $roles, array $zoneData = []): User
    {
        $user->syncRoles($this->sanitizeRoles($roles));
        $user->syncZone($zoneData);

        return $user->fresh(['roles', 'zoneable']);
    }

    public function deactivate(User $user, string $reason, User $actor): User
    {
        $user->forceFill([
            'is_active' => false,
            'deactivation_reason' => trim($reason),
            'deactivated_at' => now(),
            'deactivated_by' => $actor->id,
        ])->save();

        return $user->fresh();
    }

    public function activate(User $user): User
    {
        $user->forceFill([
            'is_active' => true,
            'deactivation_reason' => null,
            'deactivated_at' => null,
            'deactivated_by' => null,
        ])->save();

        return $user->fresh();
    }

    public function formOptions(): array
    {
        $geo = app(GeoHierarchyService::class);

        return [
            'regions' => $geo->regions(),
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
