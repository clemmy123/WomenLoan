<?php

namespace App\Rules;

use App\Models\Applicant;
use App\Models\Scopes\ApplicantAccess;
use App\Models\User;
use App\Support\IdentityNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquePhone implements ValidationRule
{
    public function __construct(
        private ?int $ignoreUserId = null,
        private ?int $ignoreApplicantId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = IdentityNormalizer::normalizePhone($value);

        if ($normalized === '') {
            return;
        }

        foreach (User::query()->when($this->ignoreUserId, fn ($query) => $query->where('id', '!=', $this->ignoreUserId))->pluck('phone') as $phone) {
            if (IdentityNormalizer::normalizePhone($phone) === $normalized) {
                $fail(__('validation.already_used', ['attribute' => __('common.phone')]));

                return;
            }
        }

        foreach (Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->when($this->ignoreApplicantId, fn ($query) => $query->where('id', '!=', $this->ignoreApplicantId))
            ->pluck('phone') as $phone) {
            if (IdentityNormalizer::normalizePhone($phone) === $normalized) {
                $fail(__('validation.already_used', ['attribute' => __('common.phone')]));

                return;
            }
        }
    }
}
