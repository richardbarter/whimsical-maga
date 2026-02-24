<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Background extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'alt_text',
        'title',
        'description',
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
}
