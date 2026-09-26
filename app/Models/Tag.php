<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\ResolvesByName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory, HasUniqueSlug, ResolvesByName;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag): void {
            if (empty($tag->slug)) {
                $tag->slug = static::generateUniqueSlug($tag->name);
            }
        });
    }

    /**
     * Get the quotes that have this tag.
     *
     * No withTimestamps(): quote_tag has no updated_at column, and Quote::tags()
     * deliberately doesn't track pivot timestamps either.
     */
    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class);
    }

    /**
     * Get the saved contexts that have this tag.
     */
    public function savedContexts(): BelongsToMany
    {
        return $this->belongsToMany(SavedContext::class, 'saved_context_tag');
    }
}
