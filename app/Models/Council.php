<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Council extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'code', 'district_id'];

    /**
     * Get the parent District this Council belongs to.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get all Wards under this specific Council.
     */
    public function wards()
    {
        return $this->hasMany(Ward::class);
    }

    /**
     * Get all Location profiles tied directly to this Council.
     * Uses the clean 'council_id' foreign key we locked into your Location model.
     */
    public function locations()
    {
        return $this->hasMany(Location::class, 'council_id');
    }

    /**
     * Polymorphic relation mapping users assigned directly to this Council level 
     * (e.g., Council Loan Officers or IT administrators).
     */
    public function users(): MorphMany
    {
        return $this->morphMany(User::class, 'zoneable');
    }
}