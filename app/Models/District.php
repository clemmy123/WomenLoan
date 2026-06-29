<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class District extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'code', 'region_id'];

    /**
     * Get the parent Region this District belongs to.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * FIXED RELATION: Points directly to your clean Council model class.
     * A single district can encompass multiple administrative councils (e.g., Municipal, Town, or District Councils).
     */
    public function councils()
    {
        return $this->hasMany(Council::class, 'district_id');
    }

    /**
     * Get all location profile records mapped to this district.
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}