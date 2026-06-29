<?php

namespace App\Services;

use App\Models\DraftLoan;
use Illuminate\Http\Request;

class DraftLoanService
{
    public function save(int $userId, string $trackId, Request $request, array $except = ['_token', 'form_action']): DraftLoan
    {
        return DraftLoan::updateOrCreate(
            ['track_id' => $trackId, 'user_id' => $userId],
            ['form_data' => $request->except($except)]
        );
    }

    public function deleteByTrackId(string $trackId): void
    {
        DraftLoan::where('track_id', $trackId)->delete();
    }
}
