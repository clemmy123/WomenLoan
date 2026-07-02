<?php

namespace App\Models;

use App\Models\Council;
use App\Models\Region;
use App\Models\Scopes\ApplicantAccess;
use App\Models\Ward;
use App\Models\Concerns\HasHashid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasHashid, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'zoneable_type',
        'zoneable_id',
        'is_active',
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
        ];
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
