<?php

namespace App\Models;

use App\Models\Concerns\HasHashid;
use App\Models\Concerns\HasDisplayName;
use App\Models\Concerns\Searchable;
use App\Models\Scopes\ApplicantAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    use HasDisplayName, HasFactory, HasHashid, Searchable;

    protected $fillable = [
        'nin', 'first_name', 'middle_name', 'last_name', 'full_name',
        'dob', 'sex', 'marital_status', 'nationality', 'phone', 'email',
        'photo_path', 'signature_path', 'nida_verified', 'nida_verified_at',
        'issuer_date', 'location_id', 'attachment', 'user_id',
    ];

    protected $casts = [
        'dob' => 'date',
        'issuer_date' => 'date',
        'nida_verified' => 'boolean',
        'nida_verified_at' => 'datetime',
    ];

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
