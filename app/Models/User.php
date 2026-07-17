<?php

namespace App\Models;

use App\Models\Council;
use App\Models\Region;
use App\Models\Scopes\ApplicantAccess;
use App\Models\Ward;
use App\Services\LoanQueryService;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasHashid;
use App\Models\Concerns\Searchable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, CausesActivity, HasFactory, HasHashid, HasRoles, Notifiable, Searchable;

    protected $fillable = [
        'check_number',
        'first_name',
        'middle_name',
        'last_name',
        'name',
        'email',
        'phone',
        'nin',
        'dob',
        'sex',
        'nationality',
        'nida_photo_path',
        'nida_verified_at',
        'password',
        'zoneable_type',
        'zoneable_id',
        'is_active',
        'must_change_password',
        'temporary_password_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'login_locked_until' => 'datetime',
            'login_locked_permanently' => 'boolean',
            'must_change_password' => 'boolean',
            'temporary_password_expires_at' => 'datetime',
            'dob' => 'date',
            'nida_verified_at' => 'datetime',
        ];
    }

    public function mustChangePassword(): bool
    {
        return (bool) $this->must_change_password;
    }

    public function temporaryPasswordExpired(): bool
    {
        return $this->must_change_password
            && $this->temporary_password_expires_at !== null
            && $this->temporary_password_expires_at->isPast();
    }

    public function markTemporaryPasswordIssued(): void
    {
        $this->forceFill([
            'must_change_password' => true,
            'temporary_password_expires_at' => null,
        ])->save();
    }

    public function startTemporaryPasswordWindow(): void
    {
        if (! $this->must_change_password || $this->temporary_password_expires_at !== null) {
            return;
        }

        $this->forceFill([
            'temporary_password_expires_at' => now()->addMinutes(
                (int) config('wdf.temporary_password_minutes', 2)
            ),
        ])->save();
    }

    public function clearTemporaryPasswordRequirement(): void
    {
        $this->forceFill([
            'must_change_password' => false,
            'temporary_password_expires_at' => null,
        ])->save();
    }

    public function applicant(): HasOne
    {
        return $this->hasOne(Applicant::class)->withoutGlobalScope(ApplicantAccess::class);
    }

    public function hasCompletedProfile(): bool
    {
        if ($this->relationLoaded('applicant')) {
            return $this->applicant !== null;
        }

        return $this->applicant()->exists();
    }

    public function hasLoanApplication(): bool
    {
        return app(LoanQueryService::class)->userHasLoanApplication($this);
    }

    public function zoneable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isApplicant(): bool
    {
        return $this->hasRole('applicant');
    }

    public function displayRole(): string
    {
        return $this->roles->first()?->name ?? 'user';
    }

    public function syncZone(array $data): void
    {
        $zoneMap = [
            'region' => Region::class,
            'council' => Council::class,
            'ward' => Ward::class,
        ];

        if (! empty($data['zone_type']) && ! empty($data['zone_id']) && isset($zoneMap[$data['zone_type']])) {
            $this->update([
                'zoneable_type' => $zoneMap[$data['zone_type']],
                'zoneable_id' => $data['zone_id'],
            ]);

            return;
        }

        $this->update(['zoneable_type' => null, 'zoneable_id' => null]);
    }
}
