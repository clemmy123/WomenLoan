<?php

namespace App\Rules;

use App\Support\IdentityNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TanzaniaPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = IdentityNormalizer::normalizePhone($value);

        if (! preg_match('/^255[67]\d{8}$/', $normalized)) {
            $customKey = "validation.custom.{$attribute}.phone";
            $custom = __($customKey);

            $fail($custom === $customKey ? __('validation.phone') : $custom);
        }
    }
}
