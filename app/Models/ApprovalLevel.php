<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLevel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_id',
        'user_id',
        'step_number',
        'action_taken',
        'proposed_amount',
        'attachment_path',
        'comments',
    ];

    /**
     * Get the loan application linked to this specific approval level entry.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the officer/user who processed this workflow level.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}