<?php

namespace App\Models;

use App\Support\GroupLeadershipRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanGroupMember extends Model
{
    protected $fillable = [
        'loan_group_id',
        'applicant_id',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'nin',
        'dob',
        'email',
        'phone',
        'sex',
        'marital_status',
        'is_group_leader',
        'leadership_role',
    ];

    protected $casts = [
        'dob' => 'date',
        'is_group_leader' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(LoanGroup::class, 'loan_group_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function leadershipRoleLabel(): ?string
    {
        return GroupLeadershipRole::label($this->leadership_role);
    }
}
