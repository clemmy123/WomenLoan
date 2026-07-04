<?php

namespace App\Http\Requests\Concerns;

use App\Support\IdentityNormalizer;

trait NormalizesIdentityFields
{
    protected function normalizeIdentityInput(array $fields = ['nin', 'phone', 'email']): void
    {
        $merge = [];

        foreach ($fields as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $merge[$field] = match ($field) {
                'nin' => IdentityNormalizer::normalizeNin($this->input($field)),
                'phone' => IdentityNormalizer::normalizePhone($this->input($field)),
                'email' => IdentityNormalizer::normalizeEmail($this->input($field)),
                default => $this->input($field),
            };
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    protected function normalizeMemberIdentityRows(string $key = 'members'): void
    {
        $rows = $this->input($key, []);

        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (isset($row['nin'])) {
                $rows[$index]['nin'] = IdentityNormalizer::normalizeNin($row['nin']);
            }

            if (isset($row['phone'])) {
                $rows[$index]['phone'] = IdentityNormalizer::normalizePhone($row['phone']);
            }

            if (isset($row['email']) && filled($row['email'])) {
                $rows[$index]['email'] = IdentityNormalizer::normalizeEmail($row['email']);
            }
        }

        $this->merge([$key => $rows]);
    }
}
