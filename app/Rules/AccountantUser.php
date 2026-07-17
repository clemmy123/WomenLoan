<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AccountantUser implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! User::role('accountant')->whereKey($value)->exists()) {
            $fail(__('validation.exists', ['attribute' => $attribute]));
        }
    }
}
