<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Scopes\ApplicantAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon; // <-- Ensure Carbon is accessible for safe type-checking

class LoanPayment extends Model
{
    use Auditable, HasFactory;

    /** @var list<string> */
    protected array $auditExclude = [
        'payment_history',
    ];

    protected $fillable = [
        'loan_id',
        'amount_requested',
        'amount_disbursed',
        'interest_amount',
        'amount_paid',
        'outstanding_debt',
        'grace_period_days',
        'start_date',
        'end_date',
        'payment_interval',
        'notes',
        'payment_history',
    ];

    /**
     * The attributes that should be cast.
     * By casting these fields to 'date' or 'datetime', Laravel automatically
     * transforms the raw database string into a Carbon instance when retrieved.
     */
    protected $casts = [
        'payment_history' => 'array',
        'start_date'      => 'date', // <-- Ensures start_date is a Carbon instance
        'end_date'        => 'date', // <-- Ensures end_date is a Carbon instance
    ];

    protected static function booted()
    {
        static::addGlobalScope(new ApplicantAccess);

        static::creating(function ($payment) {
            $disbursed = (float) $payment->amount_disbursed;
            $interestCalculated = $disbursed * 0.16;
            $totalPayableAmount = $disbursed + $interestCalculated;

            if ($payment->interest_amount === null || $payment->interest_amount === '' || $payment->interest_amount === '0') {
                $payment->interest_amount = (string) $interestCalculated;
            }

            if ($payment->outstanding_debt === null || $payment->outstanding_debt === '') {
                $payment->outstanding_debt = (string) $totalPayableAmount;
            }

            if ($payment->amount_paid === null || $payment->amount_paid === '') {
                $payment->amount_paid = '0';
            }
        });
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function group()
    {
        return $this->belongsTo(LoanGroup::class, 'loan_group_id');
    }

    /**
     * FIXED ACCESSOR: Safely evaluates if the loan term is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        // 1. If no end date is set, it cannot be overdue
        if (!$this->end_date) {
            return false;
        }
        
        // 2. Safe parsing fallback: If for any reason it wasn't cast to a Carbon instance, parse it manually
        $endDate = $this->end_date instanceof Carbon ? $this->end_date : Carbon::parse($this->end_date);
        
        // 3. Run the evaluation against the current timestamp and outstanding debt parameters
        return $endDate->isPast() && (float) $this->outstanding_debt > 0;
    }

    public function graceEndsAt(): ?Carbon
    {
        if (! $this->start_date) {
            return null;
        }

        $start = $this->start_date instanceof Carbon
            ? $this->start_date->copy()
            : Carbon::parse($this->start_date);

        return $start->startOfDay()->addDays((int) $this->grace_period_days);
    }

    public function isInGracePeriod(): bool
    {
        if ((float) $this->outstanding_debt <= 0) {
            return false;
        }

        $ends = $this->graceEndsAt();

        return $ends !== null && now()->lt($ends);
    }

    public function getRepaymentProgressPercentageAttribute(): int
    {
        $disbursed = (float) $this->amount_disbursed;
        $interest  = (float) $this->interest_amount;
        
        $totalTotalLiability = $disbursed + $interest;
        if ($totalTotalLiability <= 0) {
            return 0;
        }

        $paid = (float) $this->amount_paid;
        
        return (int) min(100, (($paid / $totalTotalLiability) * 100));
    }
}