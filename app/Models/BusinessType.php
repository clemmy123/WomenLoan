<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessType extends Model
{
    protected $fillable = [
        'business_sector_id',
        'name',
        'sort_order',
    ];

    public function businessSector(): BelongsTo
    {
        return $this->belongsTo(BusinessSector::class);
    }
}
