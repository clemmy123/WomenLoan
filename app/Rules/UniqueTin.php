<?php

namespace App\Rules;

use App\Models\BusinessDetails;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueTin implements ValidationRule
{
    public function __construct(
        private ?int $ignoreBusinessDetailsId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tin = trim((string) $value);

        if ($tin === '') {
            return;
        }

        $exists = BusinessDetails::query()
            ->where('tin_number', $tin)
            ->when($this->ignoreBusinessDetailsId, fn ($query) => $query->where('id', '!=', $this->ignoreBusinessDetailsId))
            ->exists();

        if ($exists) {
            $fail(__('validation.already_used', ['attribute' => __('loans.tin_number')]));
        }
    }
}
