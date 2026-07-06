<?php

namespace App\Services;

use App\Models\DraftLoan;
use Illuminate\Http\Request;

class DraftLoanService
{
    public function save(int $userId, string $trackId, Request $request, array $except = ['_token', 'form_action']): DraftLoan
    {
        $except = array_merge($except, [
            'business_proposal_document',
            'business_registration_attachment',
        ]);

        $incoming = collect($request->except($except))
            ->map(fn ($value) => is_scalar($value) || $value === null ? $value : null)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        $existing = DraftLoan::query()
            ->where('track_id', $trackId)
            ->where('user_id', $userId)
            ->first();

        $formData = array_merge($existing?->form_data ?? [], $incoming);

        $formData['step'] = max(1, min(6, (int) (
            $request->input('step')
            ?? $formData['step']
            ?? $existing?->form_data['step']
            ?? 1
        )));

        if (empty($formData['loan_type'])) {
            $applicant = auth()->user()?->applicant()->withoutGlobalScope(\App\Models\Scopes\ApplicantAccess::class)->first();

            if ($applicant?->preferred_loan_type) {
                $formData['loan_type'] = $applicant->preferred_loan_type;
            }
        }

        return DraftLoan::updateOrCreate(
            ['track_id' => $trackId, 'user_id' => $userId],
            ['form_data' => $formData]
        );
    }

    public function deleteByTrackId(string $trackId): void
    {
        DraftLoan::where('track_id', $trackId)->delete();
    }
}
