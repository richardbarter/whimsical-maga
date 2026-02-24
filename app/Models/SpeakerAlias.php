<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeakerAlias extends Model
{
    protected $fillable = [
        'speaker_id',
        'alias',
    ];

    public function speaker(): BelongsTo
    {
        return $this->belongsTo(Speaker::class);
    }
}
