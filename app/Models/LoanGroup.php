<?php

namespace App\Models;

use App\Models\Concerns\HasHashid;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanGroup extends Model
{
    use HasFactory, HasHashid, Searchable;

    protected $fillable = [
        'name',
        'registration_number',
        'phone',
        'email',
    ];

    public function applicants()
    {
        return $this->belongsToMany(Applicant::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'loan_group_id');
    }

    public function loanPayments()
    {
        return $this->hasManyThrough(LoanPayment::class, Loan::class, 'loan_group_id', 'loan_id');
    }

    public function getTotalRequestedAttribute(): float
    {
        return (float) $this->loans()->sum('requested_amount');
    }

    public function getTotalRemainingDebtAttribute(): float
    {
        return (float) $this->loans()->sum('debt');
    }

    public function getMemberNamesListAttribute(): string
    {
        return $this->applicants->map(fn (Applicant $applicant) => $applicant->display_name)->filter()->implode(', ');
    }

    public function getHasActiveDebtAttribute(): bool
    {
        return $this->loans()->where('debt', '>', 0)->exists();
    }
}
