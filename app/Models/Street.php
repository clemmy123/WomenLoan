<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Street extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Perfectly clean—maps precisely to the local Ward tier.
     */
    protected $fillable = ['name', 'code', 'ward_id'];

    /**
     * Get the Ward that this Street/Mtaa/Kijiji belongs to.
     */
    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Get all compound location records utilizing this specific street line.
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}