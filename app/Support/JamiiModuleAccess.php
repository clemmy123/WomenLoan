<?php

namespace App\Support;

use App\Models\User;

/**
 * Jamii shell modules a user may open after SSO.
 * Phase 1: WDF only. Later expand by role / service grants.
 */
class JamiiModuleAccess
{
    /**
     * @return list<string>
     */
    public static function idsFor(User $user): array
    {
        // Applicants and all current WDF staff use Women Development Fund.
        // Additional modules will be appended here per role when connected.
        return ['wdf'];
    }

    public static function hasMultiple(User $user): bool
    {
        return count(self::idsFor($user)) > 1;
    }
}
