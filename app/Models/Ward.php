<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Ward extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'code', 'council_id', 'district_id'];

    /**
     * FIXED RELATION: Points directly to your clean Council model class.
     */
    public function council()
    {
        return $this->belongsTo(Council::class, 'council_id');
    }

    /**
     * Get the District this Ward belongs to.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get all Streets/Mitaa/Vijiji under this Ward.
     */
    public function streets()
    {
        return $this->hasMany(Street::class);
    }

    /**
     * Get all Location profiles assigned to this Ward.
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Polymorphic relation mapping users assigned directly to this Ward level 
     * (e.g., Ward Executive Officers / WEOs).
     */
    public function users(): MorphMany
    {
        return $this->morphMany(User::class, 'zoneable');
    }
}