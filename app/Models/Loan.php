<?php

namespace App\Models;

use App\Models\Concerns\HasHashid;
use App\Models\Scopes\ApprovalLevelScope;
use App\Services\LoanTrackIdGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Loan extends Model
{
    use HasFactory, HasHashid;

    protected $fillable = [
        'loan_track_id',
        'applicant_id',
        'loan_group_id',
        'user_id',
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
        'approval_history',
        'approved_by',
        'comments',
        'officer_id',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'proposed_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'date_issued' => 'date',
        'approval_history' => 'array',
    ];

    public const TERMINAL_STATUSES = ['disbursed', 'declined_by_applicant', 'rejected'];

    /** Statuses where the applicant may still revise the application (ward step only). */
    public const APPLICANT_EDITABLE_STATUSES = ['pending', 'received'];

    public function isEditableByApplicant(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user || $this->user_id !== $user->id) {
            return false;
        }

        return $this->current_step === 1
            && in_array($this->status, self::APPLICANT_EDITABLE_STATUSES, true);
    }

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function businessDetails(): HasOne
    {
        return $this->hasOne(BusinessDetails::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    public function scopeForUser(Builder $query, ?int $userId = null): Builder
    {
        $userId ??= Auth::id();

        return $query->where('user_id', $userId);
    }

    public static function findByHashidUnscoped(string $hash): ?static
    {
        $id = app(\App\Services\HashidService::class)->decode($hash);

        if ($id === null) {
            return null;
        }

        return static::withoutGlobalScope(ApprovalLevelScope::class)->find($id);
    }

    public static function findByHashidUnscopedOrFail(string $hash): static
    {
        return static::findByHashidUnscoped($hash) ?? abort(404);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new ApprovalLevelScope);

        static::creating(function (Loan $loan) {
            $loan->current_step = $loan->current_step ?? 1;
            $loan->status = $loan->status ?? 'pending';

            if (empty($loan->loan_track_id)) {
                $loan->loan_track_id = app(LoanTrackIdGenerator::class)->next();
            }
        });
    }
}
