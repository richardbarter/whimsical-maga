<?php

namespace App\Models;

use App\Enums\SubjectSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A study, report, ruling or article that a subject's full details rely on.
 */
class SubjectSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'title',
        'source_type',
        'archived_url',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => SubjectSourceType::class,
            'position' => 'integer',
        ];
    }

    /**
     * Get the subject this source backs up.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
