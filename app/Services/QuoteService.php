<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Quote;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    /**
     * Quote columns taken directly from the validated request. The speaker, slug,
     * author and relations are derived separately.
     */
    private const QUOTE_ATTRIBUTES = [
        'text',
        'context',
        'location',
        'occurred_at',
        'is_verified',
        'is_featured',
        'status',
        'quote_type',
        'quote_type_note',
        'claim',
        'reality_check',
    ];

    public function __construct(private SpeakerService $speakerService) {}

    /**
     * Create a quote with its speaker, tags, categories and sources in one transaction.
     *
     * @param  array<string, mixed>  $data  Validated QuoteRequest data
     */
    public function create(array $data, User $author): Quote
    {
        return DB::transaction(function () use ($data, $author): Quote {
            $quote = Quote::create([
                ...Arr::only($data, self::QUOTE_ATTRIBUTES),
                'speaker_id' => $this->speakerService->resolveFromName($data['speaker']),
                'slug' => Quote::generateSlug($data['text']),
                'user_id' => $author->id,
            ]);

            $this->syncRelations($quote, $data);

            return $quote;
        });
    }

    /**
     * Update a quote and replace its relations in one transaction.
     *
     * The slug is only regenerated when the text changes, so existing links keep working.
     *
     * @param  array<string, mixed>  $data  Validated QuoteRequest data
     */
    public function update(Quote $quote, array $data): Quote
    {
        return DB::transaction(function () use ($quote, $data): Quote {
            $quote->update([
                ...Arr::only($data, self::QUOTE_ATTRIBUTES),
                'speaker_id' => $this->speakerService->resolveFromName($data['speaker']),
                'slug' => $data['text'] === $quote->text
                    ? $quote->slug
                    : Quote::generateSlug($data['text'], $quote->id),
            ]);

            $this->syncRelations($quote, $data);

            return $quote;
        });
    }

    /**
     * Tags and categories are {id, name} pairs; new ones (id=null) are created by name.
     *
     * Sources have no natural key to sync on, so they are replaced wholesale — the
     * request always carries the full desired set. Must run inside the caller's
     * transaction so a failed insert can't leave the quote with its sources deleted.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncRelations(Quote $quote, array $data): void
    {
        $quote->tags()->sync(Tag::resolveIds($data['tags'] ?? null));
        $quote->categories()->sync(Category::resolveIds($data['categories'] ?? null));

        $quote->sources()->delete();
        $quote->sources()->createMany($data['sources'] ?? []);
    }
}
