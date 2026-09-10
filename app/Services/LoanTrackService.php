<?php

namespace App\Services;

use App\Models\DraftLoan;
use App\Models\Loan;
use App\Models\Scopes\ApprovalLevelScope;
use App\Models\User;

class LoanTrackService
{
    public function normalizeTrackId(string $trackId): string
    {
        $trackId = strtoupper(trim($trackId));

        if (preg_match('/^\d+$/', $trackId)) {
            return 'WL' . str_pad($trackId, 6, '0', STR_PAD_LEFT);
        }

        return $trackId;
    }

    public function findLoan(string $trackId): ?Loan
    {
        $trackId = $this->normalizeTrackId($trackId);

        return Loan::withoutGlobalScope(ApprovalLevelScope::class)
            ->where('loan_track_id', $trackId)
            ->first();
    }

    public function findDraft(string $trackId): ?DraftLoan
    {
        $trackId = $this->normalizeTrackId($trackId);

        return DraftLoan::where('track_id', $trackId)->first();
    }

    /**
     * @return array{loan: ?Loan, draft: ?DraftLoan, trackId: string}|null
     */
    public function resolve(User $user, string $rawTrackId): ?array
    {
        $trackId = $this->normalizeTrackId($rawTrackId);

        $loan = $this->findLoan($trackId);

        if ($loan) {
            $this->authorizeLoanView($user, $loan);

            return ['loan' => $loan, 'draft' => null, 'trackId' => $trackId];
        }

        $draft = $this->findDraft($trackId);

        if ($draft) {
            $this->authorizeDraftView($user, $draft);

            return ['loan' => null, 'draft' => $draft, 'trackId' => $trackId];
        }

        return null;
    }

    public function canViewFullLoanDetails(User $user, Loan $loan): bool
    {
        if ($user->hasRole('applicant')) {
            return (int) $loan->user_id === (int) $user->id;
        }

        if ($user->can('view all loans')) {
            return true;
        }

        return Loan::whereKey($loan->id)->exists();
    }

    public function canResumeDraft(User $user, DraftLoan $draft): bool
    {
        return (int) $draft->user_id === (int) $user->id
            && $user->can('create loan application');
    }

    private function authorizeLoanView(User $user, Loan $loan): void
    {
        if ($user->hasRole('applicant') && (int) $loan->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function authorizeDraftView(User $user, DraftLoan $draft): void
    {
        if ((int) $draft->user_id !== (int) $user->id) {
            abort(403);
        }
    }
}
