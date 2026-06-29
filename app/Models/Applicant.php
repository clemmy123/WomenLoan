<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'nin', 'first_name', 'middle_name', 'last_name', 'full_name',
        'dob', 'sex', 'marital_status', 'nationality', 'phone', 'email',
        'photo_path', 'signature_path', 'nida_verified', 'nida_verified_at',
        'issuer_date', 'location_id', 'attachment', 'user_id'
    ];

    protected $casts = [
        'dob' => 'date',
        'issuer_date' => 'date',
        'nida_verified' => 'boolean',
        'nida_verified_at' => 'datetime',
    ];

    // =========================================================================
    // HELPERS & ACCESSORS
    // =========================================================================

    /**
     * Helper to get the Region ID from the associated Street/Location.
     * This is essential for your regional filtering requirements.
     */
    public function getRegionIdAttribute(): ?int
    {
        return $this->location ? $this->location->region_id : null;
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => str_replace([' ', '+'], '', $value),
        );
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pointing to Street. Ensure your Street model belongsTo(Region::class)
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Street::class, 'location_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'applicant_id');
    }

    /**
     * Many-to-Many relationship with LoanGroups.
     * We use this to verify if an applicant is already assigned to a group.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(LoanGroup::class, 'applicant_loan_group', 'applicant_id', 'loan_group_id');
    }
}