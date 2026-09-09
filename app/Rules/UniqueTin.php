<?php

namespace App\Rules;

use App\Models\BusinessDetails;
use App\Support\IdentityNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueTin implements ValidationRule
{
    public function __construct(
        private ?int $ignoreBusinessDetailsId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = substr(IdentityNormalizer::normalizeTin($value), 0, 9);

        if ($digits === '') {
            return;
        }

        $exists = BusinessDetails::query()
            ->when($this->ignoreBusinessDetailsId, fn ($query) => $query->where('id', '!=', $this->ignoreBusinessDetailsId))
            ->get(['tin_number'])
            ->contains(function (BusinessDetails $row) use ($digits): bool {
                $storedDigits = substr(IdentityNormalizer::normalizeTin($row->tin_number), 0, 9);

                return $storedDigits !== '' && $storedDigits === $digits;
            });

        if ($exists) {
            $fail(__('validation.already_used', ['attribute' => __('loans.tin_number')]));
        }
    }
}
