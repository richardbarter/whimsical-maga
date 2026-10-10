<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * An image uploaded from the full details editor. The subject's HTML refers to it by URL.
 */
class SubjectImage extends Model
{
    use HasFactory, SoftDeletes;

    /** Storage folder for the files; RichTextSanitizer only allows images from here. */
    public const FOLDER = 'subject-images';

    protected $appends = ['url'];

    protected $fillable = [
        'file_path',
        'alt_text',
        'caption',
        'credit',
        'file_size',
        'dimensions',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * Get the subject this image belongs to.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk()->url($this->file_path));
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (SubjectImage $image) {
            Storage::disk()->delete($image->file_path);
        });
    }
}
