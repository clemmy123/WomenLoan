<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DraftLoan extends Model
{
    use HasFactory;

    // ✅ Laravel will automatically map DraftLoan → draft_loans table
    // No need to override $table unless you use a custom name

    // Columns allowed for mass assignment
    protected $fillable = [
        'user_id',
        'track_id',
        'form_data',
    ];

    // Automatically cast JSON <-> array for form_data
    protected $casts = [
        'form_data' => 'array',
    ];

    // Relationship: each draft belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
