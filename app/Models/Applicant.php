<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasHashid;
use App\Models\Concerns\HasDisplayName;
use App\Models\Concerns\Searchable;
use App\Models\Scopes\ApplicantAccess;
use App\Support\AgeCalculator;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    use Auditable, HasDisplayName, HasFactory, HasHashid, Searchable;

    public const MARITAL_STATUSES = ['Single', 'Married', 'Divorced', 'Widowed'];

    public const LOAN_TYPES = ['individual', 'group'];

    protected $fillable = [
        'nin', 'first_name', 'middle_name', 'last_name', 'full_name',
        'dob', 'sex', 'marital_status', 'preferred_loan_type', 'has_disability',
        'nationality', 'phone', 'email',
        'photo_path', 'signature_path', 'nida_verified', 'nida_verified_at',
        'issuer_date', 'location_id', 'postal_code', 'po_box', 'attachment', 'user_id',
    ];

    protected $casts = [
        'dob' => 'date',
        'issuer_date' => 'date',
        'nida_verified' => 'boolean',
        'nida_verified_at' => 'datetime',
        'has_disability' => 'boolean',
    ];

    public function age(?CarbonInterface $asOf = null): ?int
    {
        return AgeCalculator::years($this->dob, $asOf);
    }

    public function isWidowed(): bool
    {
        return $this->marital_status === 'Widowed';
    }

    public function prefersGroupLoan(): bool
    {
        return $this->preferred_loan_type === 'group';
    }

    public function prefersIndividualLoan(): bool
    {
        return $this->preferred_loan_type === 'individual';
    }

    public function getRegionIdAttribute(): ?int
    {
        $this->loadMissing('location.ward.council.district');

        return $this->location?->ward?->council?->district?->region_id;
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => str_replace([' ', '+'], '', $value),
        );
    }

    public function scopeInRegion(Builder $query, int $regionId): Builder
    {
        return $query->whereHas('location.ward.council.district', fn (Builder $q) => $q->where('region_id', $regionId));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Street::class, 'location_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'applicant_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(LoanGroup::class, 'applicant_loan_group', 'applicant_id', 'loan_group_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new ApplicantAccess);
    }
}
