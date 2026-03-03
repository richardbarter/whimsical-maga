<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Background extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['url'];

    protected $fillable = [
        'file_path',
        'alt_text',
        'title',
        'description',
        'credit',
        'source_url',
        'file_size',
        'dimensions',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->file_path));
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (Background $background) {
            Storage::disk('public')->delete($background->file_path);
        });
    }
}
