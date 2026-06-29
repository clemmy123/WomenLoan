<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\ApplicantAccess; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'registration_number', // Useful to prevent fraud and track officially registered groups
        'phone',               // Primary contact point for group leaders/secretaries
        'email',               // Optional notification point
    ];

    /**
     * =========================================================================
     * CORE STRUCTURAL RELATIONSHIPS
     * =========================================================================
     */

    /**
     * Get the individual group members/applicants assigned to this group.
     */
    public function applicants()
    {
        return $this->belongsToMany(Applicant::class);
    }

    /**
     * Get all loan applications tied to this group.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class, 'loan_group_id'); // Explicitly mapped to match your loans table column
    }

    /**
     * Get all recovery collections recorded directly against this group's loans.
     */
    public function loanPayments()
    {
        return $this->hasManyThrough(LoanPayment::class, Loan::class, 'loan_group_id', 'loan_id');
    }

    /**
     * =========================================================================
     * MEANINGFUL BUSINESS LOGIC CALCULATORS (CUSTOM ATTRIBUTES)
     * =========================================================================
     */

    /**
     * Calculate the total dynamic financial profile metrics of this group.
     * Accessible on views or scripts via: $group->total_requested
     */
    public function getTotalRequestedAttribute(): float
    {
        return (float) $this->loans()->sum('requested_amount');
    }

    /**
     * Calculate the entire running group liability balance.
     * Accessible on views or scripts via: $group->total_remaining_debt
     */
    public function getTotalRemainingDebtAttribute(): float
    {
        return (float) $this->loans()->sum('debt');
    }

    /**
     * FIXED FOR NIDA INTEGRATION COMPLIANCE:
     * Get a quick comma-separated list of all current member names.
     * This now uses the applicant's dynamic name resolution to prevent crashes.
     * Accessible via: $group->member_names_list
     */
    public function getMemberNamesListAttribute(): string
    {
        return $this->applicants->map(function ($applicant) {
            // Kama kuna full_name, itumie hiyo; la sivyo, unganisha majina ya NIDA kwa usalama
            if (!empty($applicant->full_name)) {
                return $applicant->full_name;
            }
            
            return trim("{$applicant->first_name} {$applicant->middle_name} {$applicant->last_name}");
        })->filter()->implode(', ');
    }

    /**
     * Determine if this group currently has any active unpaid loans outstanding.
     * Accessible via: $group->has_active_debt
     */
    public function getHasActiveDebtAttribute(): bool
    {
        return $this->loans()->where('debt', '>', 0)->exists();
    }

    /**
     * =========================================================================
     * BOOT LAYER CONSTRAINTS
     * =========================================================================
     */
    protected static function booted(): void
    {
        // Keeps your row level accessibility filtering active if needed globally
        // static::addGlobalScope(new ApplicantAccess);
    }
}