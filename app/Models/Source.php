<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'url',
        'title',
        'source_type',
        'is_primary',
        'archived_url',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the quote that this source belongs to.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
