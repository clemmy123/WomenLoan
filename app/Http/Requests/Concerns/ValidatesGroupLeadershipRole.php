<?php

namespace App\Http\Requests\Concerns;

use App\Models\LoanGroupMember;
use App\Support\GroupLeadershipRole;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesGroupLeadershipRole
{
    /** @return list<\Illuminate\Contracts\Validation\ValidationRule|string> */
    protected function leadershipRoleFieldRules(?int $groupId = null, ?int $ignoreMemberId = null): array
    {
        return ['nullable', Rule::in(GroupLeadershipRole::values())];
    }

    protected function assertExclusiveLeadershipRoleAvailable(
        Validator $validator,
        ?int $groupId,
        ?string $role,
        ?int $ignoreMemberId = null,
        string $errorKey = 'leadership_role',
    ): void {
        if (! $groupId || ! $role || GroupLeadershipRole::allowsMultiple($role)) {
            return;
        }

        $taken = LoanGroupMember::query()
            ->where('loan_group_id', $groupId)
            ->where('leadership_role', $role)
            ->when($ignoreMemberId, fn ($query) => $query->where('id', '!=', $ignoreMemberId))
            ->exists();

        if ($taken) {
            $validator->errors()->add($errorKey, __('groups.leadership_role_taken'));
        }
    }

    protected function normalizeLeadershipRoleInput(): void
    {
        if ($this->has('leadership_role') && blank($this->input('leadership_role'))) {
            $this->merge(['leadership_role' => null]);
        }

        if ($this->has('leader')) {
            $leader = $this->input('leader', []);

            if (is_array($leader) && array_key_exists('leadership_role', $leader) && blank($leader['leadership_role'])) {
                $leader['leadership_role'] = null;
                $this->merge(['leader' => $leader]);
            }
        }

        $rows = $this->input('members', []);

        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (array_key_exists('leadership_role', $row) && blank($row['leadership_role'])) {
                $rows[$index]['leadership_role'] = null;
            }
        }

        $this->merge(['members' => $rows]);
    }

    protected function validateUniqueLeadershipRolesAcrossPayload(Validator $validator): void
    {
        $roles = [];

        if ($leaderRole = $this->input('leader.leadership_role')) {
            if (! GroupLeadershipRole::allowsMultiple($leaderRole)) {
                $roles[] = $leaderRole;
            }
        }

        foreach ($this->input('members', []) as $index => $row) {
            if (! is_array($row) || blank($row['leadership_role'] ?? null)) {
                continue;
            }

            $role = $row['leadership_role'];

            if (! GroupLeadershipRole::allowsMultiple($role) && in_array($role, $roles, true)) {
                $validator->errors()->add(
                    "members.{$index}.leadership_role",
                    __('groups.leadership_role_duplicate'),
                );

                return;
            }

            if (! GroupLeadershipRole::allowsMultiple($role)) {
                $roles[] = $role;
            }
        }
    }
}
