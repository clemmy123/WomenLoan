<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;

class SecureFileAccessService
{
    /** @var list<string> */
    private const BUSINESS_DOCUMENT_COLUMNS = [
        'business_proposal_document',
        'business_registration_attachment',
        'proof_address_attachment',
        'application_letter',
        'bank_statement',
        'group_constitution',
        'group_muhtasari',
        'group_certificate',
    ];

    public function canAccess(User $user, string $path): bool
    {
        if ($path === '' || str_contains($path, '..')) {
            return false;
        }

        if ($user->nida_photo_path === $path) {
            return true;
        }

        $applicant = $user->relationLoaded('applicant')
            ? $user->applicant
            : $user->applicant()->withoutGlobalScopes()->first();

        if ($applicant?->photo_path === $path) {
            return true;
        }

        return Loan::query()
            ->where(function ($query) use ($path) {
                $query->whereHas('businessDetails', function ($business) use ($path) {
                    $business->where(function ($columns) use ($path) {
                        foreach (self::BUSINESS_DOCUMENT_COLUMNS as $column) {
                            $columns->orWhere($column, $path);
                        }
                    });
                })
                    ->orWhereHas('guarantors', fn ($guarantor) => $guarantor->where('guarantor_letter', $path))
                    ->orWhereHas('approvalLevels', fn ($level) => $level->where('attachment_path', $path));
            })
            ->exists();
    }
}
