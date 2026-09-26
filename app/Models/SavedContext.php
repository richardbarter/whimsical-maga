<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Contexts where every term appears in the subject or in one of the tag names
     * (case-insensitive). No terms means no filtering.
     *
     * @param  array<int, string>  $terms
     */
    public function scopeMatchingAllTerms(Builder $query, array $terms): Builder
    {
        foreach ($terms as $term) {
            $query->where(fn (Builder $query) => $query
                ->whereLike('subject', "%{$term}%")
                ->orWhereHas('tags', fn (Builder $tags) => $tags->whereLike('name', "%{$term}%"))
            );
        }

        return $query;
    }
}
