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

        $formData = collect($request->except($except))
            ->map(fn ($value) => is_scalar($value) || $value === null ? $value : null)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

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
