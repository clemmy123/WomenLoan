<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JumuishiProcessedEvent extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'event_uuid';

    protected $fillable = [
        'event_uuid',
        'event_type',
        'local_user_id',
    ];

    public function localUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'local_user_id');
    }
}
