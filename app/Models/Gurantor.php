<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gurantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'applicant_id',
        'name',
        'phone',
        'relationship',
        'id_number',
        'occupation',
        
        // Geolocation Matrix Tracking Keys
        'guarantor_region_id',
        'guarantor_district_id',
        'guarantor_council_id', // Standalone Council normalization
        'guarantor_ward_id',
        'guarantor_street_id',
    ];

    /**
     * Get the loan application that owns this guarantor record.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the Region where the guarantor resides.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'guarantor_region_id');
    }

    /**
     * Get the District where the guarantor resides.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'guarantor_district_id');
    }

    /**
     * Get the administrative Council managing the guarantor's area.
     */
    public function council(): BelongsTo
    {
        return $this->belongsTo(Council::class, 'guarantor_council_id');
    }

    /**
     * Get the local Ward where the guarantor resides.
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'guarantor_ward_id');
    }

    /**
     * Get the specific Street/Village location footprint of the guarantor.
     */
    public function street(): BelongsTo
    {
        return $this->belongsTo(Street::class, 'guarantor_street_id');
    }
}