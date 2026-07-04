<?php

namespace App\Rules;

use App\Support\IdentityNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TanzanianNin implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nin = IdentityNormalizer::normalizeNin($value);

        if (! preg_match('/^\d{20}$/', $nin)) {
            $fail(__('validation.nin', ['attribute' => __('applicants.nin')]));
        }
    }
}
