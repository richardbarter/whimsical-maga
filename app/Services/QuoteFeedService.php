<?php

namespace App\Services;

use App\Models\Quote;
use Illuminate\Support\Collection;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Serves the home page's published quotes in batches.
 *
 * Each visitor gets a seed; the seed fixes a shuffled order (featured quotes first), so
 * later batches continue the same order with no repeats. Only ids are shuffled, which
 * keeps it cheap and identical across database drivers.
 */
class QuoteFeedService
{
    public const BATCH_SIZE = 30;

    /** Seeds round-trip through JavaScript, so keep them well inside its safe integer range. */
    public const MAX_SEED = 2_147_483_647;

    public function newSeed(): int
    {
        return random_int(1, self::MAX_SEED);
    }

    /**
     * @return array{quotes: Collection<int, Quote>, hasMore: bool}
     */
    public function batch(int $seed, int $page): array
    {
        $orderedIds = $this->orderedIds($seed);
        $batchIds = array_slice($orderedIds, ($page - 1) * self::BATCH_SIZE, self::BATCH_SIZE);
        $positions = array_flip($batchIds);

        $quotes = Quote::with('speaker:id,name')
            ->whereKey($batchIds)
            ->get(['id', 'text', 'context', 'occurred_at', 'is_featured', 'speaker_id'])
            ->sortBy(fn (Quote $quote) => $positions[$quote->id])
            ->values();

        return [
            'quotes' => $quotes,
            'hasMore' => count($orderedIds) > $page * self::BATCH_SIZE,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function orderedIds(int $seed): array
    {
        $randomizer = new Randomizer(new Mt19937($seed));

        $featuredIds = Quote::published()->featured()->pluck('id')->all();
        $otherIds = Quote::published()->where('is_featured', false)->pluck('id')->all();

        return [
            ...$randomizer->shuffleArray($featuredIds),
            ...$randomizer->shuffleArray($otherIds),
        ];
    }
}
