<?php

namespace App\Support;

class GroupLeadershipRole
{
    public const CHAIRPERSON = 'chairperson';

    public const VICE_CHAIRPERSON = 'vice_chairperson';

    public const SECRETARY = 'secretary';

    public const TREASURER = 'treasurer';

    public const MEMBER = 'member';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::CHAIRPERSON,
            self::VICE_CHAIRPERSON,
            self::SECRETARY,
            self::TREASURER,
            self::MEMBER,
        ];
    }

    /** @return list<string> */
    public static function exclusiveValues(): array
    {
        return [
            self::CHAIRPERSON,
            self::VICE_CHAIRPERSON,
            self::SECRETARY,
            self::TREASURER,
        ];
    }

    public static function allowsMultiple(string $role): bool
    {
        return $role === self::MEMBER;
    }

    public static function label(?string $role): ?string
    {
        if ($role === null || $role === '') {
            return null;
        }

        return __('groups.leadership_roles.'.$role);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::values())
            ->mapWithKeys(fn (string $value) => [$value => self::label($value)])
            ->all();
    }
}
