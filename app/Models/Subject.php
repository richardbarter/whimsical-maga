<?php

namespace App\Models;

use App\Enums\SubjectStatus;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\ResolvesByName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A reusable explanation (e.g. "Mail-in ballots are rife with fraud") shared by the quotes
 * that make the same argument. Its full details are shown on each linked quote's page.
 */
class Subject extends Model
{
    use HasFactory, HasUniqueSlug, ResolvesByName, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'full_details',
        'status',
    ];

    /**
     * Matches the column default, so a subject created by name from the quote form is a
     * draft in memory too, not just once it's reloaded.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubjectStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Subject $subject): void {
            if (empty($subject->slug)) {
                $subject->slug = static::generateUniqueSlug($subject->name);
            }
        });
    }

    /**
     * Get the quotes that link to this subject.
     */
    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class)->withPivot('position');
    }

    /**
     * Get the evidence behind this subject's full details, in the admin's order.
     */
    public function sources(): HasMany
    {
        return $this->hasMany(SubjectSource::class)->orderBy('position');
    }

    /**
     * Get the images uploaded for this subject's full details.
     */
    public function images(): HasMany
    {
        return $this->hasMany(SubjectImage::class);
    }

    /**
     * Scope to get only published subjects.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', SubjectStatus::Published);
    }

    /**
     * Scope to the subjects a user may see: everything for admins, published only otherwise.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $user?->isAdmin() ? $query : $query->published();
    }
}
