<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessSector extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function businessTypes(): HasMany
    {
        return $this->hasMany(BusinessType::class)->orderBy('sort_order');
    }
}
