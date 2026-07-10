<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessDetails extends Model
{
    use Auditable, HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'loan_id',
        'region_id',
        'district_id',
        'council_id', // FIXED: Renamed from district_council_id to match your architectural standards
        'ward_id',
        'street_id',
        'business_name',
        'business_phone',
        'business_email', 
        'business_sector',
        'business_type',
        'tin_number',
        'proof_address_attachment',
        'business_registration_attachment',
        'business_proposal_document',
        'application_letter',
        'bank_statement',
        'group_constitution',
        'group_muhtasari',
        'group_certificate',
    ];

    /**
     * Get the Loan profile that owns these specific business details.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the Region where this business operates.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the District where this business operates.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get the Council (Halmashauri) managing this business registration.
     */
    public function council(): BelongsTo
    {
        return $this->belongsTo(Council::class, 'council_id');
    }

    /**
     * Get the local Ward (Kata) where this business is based.
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Get the specific Street/Village (Mtaa/Kijiji) footprint of the business.
     */
    public function street(): BelongsTo
    {
        return $this->belongsTo(Street::class);
    }
}