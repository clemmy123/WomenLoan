<?php

namespace App\Support;

class LoanWizardFieldMap
{
    /** @var array<string, int> */
    private const FIELD_STEPS = [
        'loan_type' => 1,
        'loan_group_id' => 1,
        'region_id' => 1,
        'district_id' => 1,
        'council_id' => 1,
        'ward_id' => 1,
        'street_id' => 1,
        'business_name' => 1,
        'business_phone' => 1,
        'business_email' => 1,
        'business_sector' => 1,
        'business_type' => 1,
        'tin_number' => 1,
        'business_proposal_document' => 1,
        'business_registration_attachment' => 1,
        'proof_address_attachment' => 1,
        'application_letter' => 1,
        'bank_statement' => 1,
        'group_constitution' => 1,
        'group_muhtasari' => 1,
        'group_certificate' => 1,
        'guarantor_first_name' => 2,
        'guarantor_middle_name' => 2,
        'guarantor_last_name' => 2,
        'guarantor_phone' => 2,
        'guarantor_nin' => 2,
        'guarantor_relationship' => 2,
        'guarantor_occupation' => 2,
        'guarantor_sex' => 2,
        'guarantor_region_id' => 2,
        'guarantor_district_id' => 2,
        'guarantor_council_id' => 2,
        'guarantor_ward_id' => 2,
        'guarantor_street_id' => 2,
        'guarantor_letter' => 2,
        'requested_amount' => 3,
        'bank_name' => 4,
        'bank_number' => 4,
        'declaration' => 5,
    ];

    public static function stepForField(?string $field): int
    {
        if ($field === null || $field === 'error') {
            return 6;
        }

        return self::FIELD_STEPS[$field] ?? 6;
    }

    public static function isLoanApplicationRequest(string $path): bool
    {
        return str_contains($path, 'loan-applications');
    }
}
