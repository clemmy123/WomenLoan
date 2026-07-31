<?php

namespace App\Http\Requests\Concerns;

trait NormalizesStaffRoleSelection
{
    protected function normalizeStaffRoleSelection(): void
    {
        if ($this->filled('role')) {
            $this->merge([
                'roles' => [(string) $this->input('role')],
            ]);

            return;
        }

        $roles = $this->input('roles');

        if (is_array($roles) && count($roles) === 1) {
            $this->merge([
                'role' => (string) reset($roles),
            ]);
        }
    }
}
