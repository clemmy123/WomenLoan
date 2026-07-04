<?php

namespace App\Rules;

use App\Models\Applicant;
use App\Models\Scopes\ApplicantAccess;
use App\Models\User;
use App\Support\IdentityNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueEmail implements ValidationRule
{
    public function __construct(
        private ?int $ignoreUserId = null,
        private ?int $ignoreApplicantId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = IdentityNormalizer::normalizeEmail($value);

        if ($email === '') {
            return;
        }

        $userExists = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->when($this->ignoreUserId, fn ($query) => $query->where('id', '!=', $this->ignoreUserId))
            ->exists();

        if ($userExists) {
            $fail(__('validation.already_used', ['attribute' => __('common.email')]));

            return;
        }

        $applicantExists = Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->when($this->ignoreApplicantId, fn ($query) => $query->where('id', '!=', $this->ignoreApplicantId))
            ->exists();

        if ($applicantExists) {
            $fail(__('validation.already_used', ['attribute' => __('common.email')]));
        }
    }
}
