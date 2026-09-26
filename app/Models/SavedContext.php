<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SavedContext extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'body',
    ];

    /**
     * Get the tags for this saved context.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'saved_context_tag');
    }
}
