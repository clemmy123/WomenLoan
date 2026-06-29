<?php

namespace App\Models\Concerns;

trait HasDisplayName
{
    public static function buildFullName(string $firstName, ?string $middleName, string $lastName): string
    {
        $middle = $middleName ? ' ' . trim($middleName) : '';

        return trim("{$firstName}{$middle} {$lastName}");
    }

    public function getDisplayNameAttribute(): string
    {
        if (! empty($this->full_name)) {
            return $this->full_name;
        }

        return self::buildFullName(
            $this->first_name ?? '',
            $this->middle_name ?? null,
            $this->last_name ?? ''
        );
    }
}
