<?php

namespace App\Http\Requests\Concerns;

trait ValidatesGroupMemberDob
{
    /** @return list<string> */
    protected function memberDobRules(bool $required = true): array
    {
        $rules = [
            'date',
            'after_or_equal:'.now()->subYears(120)->toDateString(),
            'before_or_equal:'.now()->subYears(18)->toDateString(),
        ];

        if ($required) {
            array_unshift($rules, 'required');
        }

        return $rules;
    }
}
