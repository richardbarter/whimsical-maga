<?php

namespace App\Services;

use App\Models\Speaker;
use App\Models\SpeakerAlias;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class SpeakerService
{
    /**
     * Resolve a speaker ID from a free-text name.
     *
     * Resolution order:
     *   1. Exact name match (case-insensitive)
     *   2. Alias match (case-insensitive)
     *   3. Create a new speaker
     */
    public function resolveFromName(?string $speakerName): ?int
    {
        if (! $speakerName) {
            return null;
        }

        $speaker = Speaker::whereRaw('LOWER(name) = ?', [strtolower($speakerName)])->first();

        if (! $speaker) {
            $alias = SpeakerAlias::whereRaw('LOWER(alias) = ?', [strtolower($speakerName)])
                ->with('speaker')
                ->first();
            $speaker = $alias?->speaker;
        }

        if (! $speaker) {
            $speaker = $this->createSpeaker($speakerName);
        }

        return $speaker->id;
    }

    private function createSpeaker(string $name): Speaker
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Speaker::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        try {
            return Speaker::create(['name' => $name, 'slug' => $slug]);
        } catch (UniqueConstraintViolationException) {
            // Race condition: another concurrent request created this speaker between
            // our exists() check and our create(). The DB unique constraint caught it.
            // Find and return the speaker that was just inserted.
            return Speaker::where('name', $name)->firstOrFail();
        }
    }
}
