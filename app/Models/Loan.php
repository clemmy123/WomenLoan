<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Loan
 * Represents a financial loan application within the system.
 */
class Loan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'loan_track_id',
        'applicant_id',
        'loan_group_id',
        'loan_type',
        'requested_amount',
        'proposed_amount',
        'disbursed_amount',
        'date_issued',
        'bank_name',
        'bank_number',
        'status',
        'current_step',
        'applicant_acceptance',
        'approved_by',
        'comments',
        'officer_id',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'requested_amount' => 'decimal:2',
        'proposed_amount'  => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'date_issued'      => 'date',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(LoanGroup::class, 'loan_group_id');
    }

    public function approvalLevels(): HasMany
    {
        return $this->hasMany(ApprovalLevel::class)->orderBy('created_at', 'asc');
    }

    public function loanPayments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(Gurantor::class);
    }

    public function businessDetails(): HasOne
    {
        return $this->hasOne(BusinessDetails::class);
    }

    // =========================================================================
    // BOOT INITIALIZATION
    // =========================================================================
    
    protected static function booted(): void
    {
        static::creating(function (Loan $loan) {
            // Set default status and steps
            $loan->current_step = $loan->current_step ?? 1;
            $loan->status = $loan->status ?? 'pending';

            // Auto-generate WL + 6 digit tracking ID (e.g., WL000001)
            $latestLoan = Loan::latest('id')->first();
            
            $nextNumber = 1;
            if ($latestLoan && preg_match('/WL(\d+)/', $latestLoan->loan_track_id, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            }

            $loan->loan_track_id = 'WL' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}