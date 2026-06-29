<?php

namespace App\Models;

use App\Models\Concerns\HasHashid;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasHashid;

    /** Roles that cannot be deleted. */
    public const PROTECTED = ['super_admin', 'applicant'];

    /** Roles whose permissions cannot be changed. */
    public const LOCKED_PERMISSIONS = ['super_admin'];

    public function isProtected(): bool
    {
        return in_array($this->name, self::PROTECTED, true);
    }

    public function hasLockedPermissions(): bool
    {
        return in_array($this->name, self::LOCKED_PERMISSIONS, true);
    }

    public function isDeletable(): bool
    {
        return ! $this->isProtected() && $this->users()->count() === 0;
    }
}
