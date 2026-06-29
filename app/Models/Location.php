<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Captures the full geographic hierarchy with your exact column names.
     */
    protected $fillable = [
        'region_id',
        'district_id',
        'council_id', // Kept exactly as council_id
        'ward_id',
        'street_id'
    ];

    /**
     * Get the Region this location profile belongs to.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the District this location profile belongs to.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get the administrative Council handling this application zone.
     * Points cleanly to Council::class using council_id.
     */
    public function council()
    {
        return $this->belongsTo(Council::class, 'council_id');
    }

    /**
     * Get the Ward level administrative tier for this location.
     */
    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Get the micro-local Street/Kijiji/Kitongoji line row.
     */
    public function street()
    {
        return $this->belongsTo(Street::class);
    }

    /**
     * Links this geographic block directly to the operating business profile.
     */
    public function businessDetails()
    {
        return $this->hasOne(BusinessDetails::class);
    }
}