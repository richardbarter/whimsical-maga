<?php

namespace App\Services;

use App\Models\Speaker;
use App\Models\SpeakerAlias;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

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
        try {
            // Nested transaction = savepoint when called inside QuoteService's transaction,
            // so a unique violation can be caught without aborting the outer Postgres transaction.
            return DB::transaction(fn () => Speaker::create([
                'name' => $name,
                'slug' => Speaker::generateUniqueSlug($name),
            ]));
        } catch (UniqueConstraintViolationException) {
            // Race condition: another concurrent request created this speaker between
            // our slug check and our create(). The DB unique constraint caught it.
            // Find and return the speaker that was just inserted.
            return Speaker::where('name', $name)->firstOrFail();
        }
    }
}
