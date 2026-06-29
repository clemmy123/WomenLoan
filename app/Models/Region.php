<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $fillable = ['name', 'code'];

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function businessDetails()
    {
        return $this->belongsTo(BusinessDetails::class);
    }
}
