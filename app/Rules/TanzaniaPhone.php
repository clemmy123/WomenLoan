<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TanzaniaPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = str_replace([' ', '+'], '', (string) $value);

        if (! preg_match('/^(?:255|0)[67][1-9]\d{7}$/', $normalized)) {
            $fail(__('validation.phone', ['attribute' => __('common.phone')]));
        }
    }
}
