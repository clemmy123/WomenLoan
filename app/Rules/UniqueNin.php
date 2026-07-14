<?php

namespace App\Rules;

use App\Models\Applicant;
use App\Models\LoanGroupMember;
use App\Models\Scopes\ApplicantAccess;
use App\Models\User;
use App\Support\IdentityNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueNin implements ValidationRule
{
    public function __construct(
        private ?int $ignoreApplicantId = null,
        private ?int $ignoreGroupMemberId = null,
        private ?int $ignoreUserId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nin = IdentityNormalizer::normalizeNin($value);

        if ($nin === '') {
            return;
        }

        $applicantExists = Applicant::withoutGlobalScope(ApplicantAccess::class)
            ->where('nin', $nin)
            ->when($this->ignoreApplicantId, fn ($query) => $query->where('id', '!=', $this->ignoreApplicantId))
            ->exists();

        if ($applicantExists) {
            $fail(__('validation.already_used', ['attribute' => __('applicants.nin')]));

            return;
        }

        $memberExists = LoanGroupMember::query()
            ->where('nin', $nin)
            ->when($this->ignoreGroupMemberId, fn ($query) => $query->where('id', '!=', $this->ignoreGroupMemberId))
            ->exists();

        if ($memberExists) {
            $fail(__('validation.already_used', ['attribute' => __('applicants.nin')]));

            return;
        }

        $userExists = User::query()
            ->where('nin', $nin)
            ->when($this->ignoreUserId, fn ($query) => $query->where('id', '!=', $this->ignoreUserId))
            ->exists();

        if ($userExists) {
            $fail(__('validation.already_used', ['attribute' => __('applicants.nin')]));
        }
    }
}
