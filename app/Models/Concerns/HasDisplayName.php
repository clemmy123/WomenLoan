<?php

namespace App\Models\Concerns;

trait HasDisplayName
{
    public static function buildFullName(string $firstName, ?string $middleName, string $lastName): string
    {
        $middle = $middleName ? ' ' . trim($middleName) : '';

        return trim("{$firstName}{$middle} {$lastName}");
    }

    /**
     * @return array{first_name: string, middle_name: string|null, last_name: string}
     */
    public static function splitFullName(string $fullName): array
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($fullName)) ?: []));

        if ($parts === []) {
            return ['first_name' => '', 'middle_name' => null, 'last_name' => ''];
        }

        if (count($parts) === 1) {
            return ['first_name' => $parts[0], 'middle_name' => null, 'last_name' => ''];
        }

        if (count($parts) === 2) {
            return ['first_name' => $parts[0], 'middle_name' => null, 'last_name' => $parts[1]];
        }

        return [
            'first_name' => $parts[0],
            'middle_name' => implode(' ', array_slice($parts, 1, -1)),
            'last_name' => $parts[array_key_last($parts)],
        ];
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
