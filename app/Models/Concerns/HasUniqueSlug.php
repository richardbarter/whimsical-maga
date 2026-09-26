<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

trait HasUniqueSlug
{
    /**
     * Generate a slug from the given text that no other row uses.
     *
     * Soft-deleted rows are included in the check because the database unique
     * index covers them too. Collisions get an incrementing suffix (-1, -2, …).
     * Pass $ignoreId when regenerating the slug of an existing row so it does not
     * collide with itself.
     */
    public static function generateUniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($text);
        $slug = $baseSlug;
        $counter = 1;

        while (static::slugIsTaken($slug, $ignoreId)) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }

    protected static function slugIsTaken(string $slug, ?int $ignoreId): bool
    {
        $query = static::query();

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            $query->withTrashed();
        }

        return $query
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
