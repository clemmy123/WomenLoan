<?php

namespace App\Rules;

use App\Models\Applicant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ApplicantNotInAnyGroup implements ValidationRule
{
    public function __construct(private ?int $exceptGroupId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = DB::table('applicant_loan_group')->where('applicant_id', $value);

        if ($this->exceptGroupId) {
            $query->where('loan_group_id', '!=', $this->exceptGroupId);
        }

        if ($query->exists()) {
            $name = Applicant::find($value)?->display_name ?? 'Applicant';
            $fail(__('messages.applicant_already_in_group', ['name' => $name]));
        }
    }
}
