<?php

namespace App\Models;

use App\Observers\QuoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[ObservedBy(QuoteObserver::class)]
class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'text',
        'speaker_id',
        'slug',
        'context',
        'location',
        'occurred_at',
        'published_at',
        'is_verified',
        'is_featured',
        'status',
        'quote_type',
        'quote_type_note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'date',
            'published_at' => 'datetime',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'view_count' => 'integer',
        ];
    }

    /**
     * Get the speaker who said this quote.
     */
    public function speaker(): BelongsTo
    {
        return $this->belongsTo(Speaker::class);
    }

    /**
     * Get the user who created this quote.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sources for this quote.
     */
    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    /**
     * Generate a unique slug from the first 8 words of the quote text.
     *
     * If the base slug is already taken, appends an incrementing counter (-2, -3, …).
     * Pass $excludeId when regenerating a slug for an existing quote so it does not
     * collide with itself.
     */
    public static function generateSlug(string $text, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug(implode(' ', array_slice(explode(' ', $text), 0, 8)));
        $slug = $baseSlug;
        $counter = 1;

        while (
            static::where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }

    /**
     * Get the tags for this quote.
     *
     * The quote_tag pivot intentionally omits timestamps — the relationship date
     * is not meaningful enough to warrant tracking.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Get the categories for this quote.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    /**
     * Scope to get only published quotes.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get only featured quotes.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
