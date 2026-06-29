<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LoanTrackIdGenerator
{
    public function next(): string
    {
        $maxNum = DB::table('loans')
            ->selectRaw('CAST(SUBSTR(loan_track_id, 3) AS UNSIGNED) as num')
            ->orderByDesc('num')
            ->value('num');

        return 'WL' . str_pad(((int) $maxNum) + 1, 6, '0', STR_PAD_LEFT);
    }
}
