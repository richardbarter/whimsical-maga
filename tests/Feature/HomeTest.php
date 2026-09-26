<?php

namespace Tests\Feature;

use App\Models\Background;
use App\Models\Quote;
use App\Services\QuoteFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_ok(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_home_page_passes_published_quotes_to_view(): void
    {
        Quote::factory()->published()->count(3)->create();

        $this->get(route('home'))
            ->assertInertia(fn ($page) => $page
                ->component('Public/Home')
                ->has('quotes', 3)
            );
    }

    public function test_home_page_excludes_draft_and_pending_quotes(): void
    {
        Quote::factory()->published()->create();
        Quote::factory()->create(['status' => 'draft']);
        Quote::factory()->create(['status' => 'pending']);

        $this->get(route('home'))
            ->assertInertia(fn ($page) => $page
                ->has('quotes', 1)
            );
    }

    public function test_home_page_includes_featured_quotes(): void
    {
        Quote::factory()->published()->featured()->create();
        Quote::factory()->published()->create();

        $this->get(route('home'))
            ->assertInertia(fn ($page) => $page
                ->has('quotes', 2)
                ->where('quotes.0.is_featured', true)
            );
    }

    public function test_home_page_passes_backgrounds_to_view(): void
    {
        Background::factory()->count(2)->create();

        $this->get(route('home'))
            ->assertInertia(fn ($page) => $page
                ->has('backgrounds', 2)
            );
    }

    public function test_home_page_handles_no_published_quotes(): void
    {
        Quote::factory()->count(3)->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('quotes', 0)
            );
    }

    public function test_home_page_handles_no_backgrounds(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('backgrounds', 0)
            );
    }

    public function test_quotes_include_speaker_relationship(): void
    {
        Quote::factory()->published()->create();

        $this->get(route('home'))
            ->assertInertia(fn ($page) => $page
                ->has('quotes.0.speaker')
                ->has('quotes.0.speaker.name')
            );
    }

    // -------------------------------------------------------------------------
    // Batched quote feed
    // -------------------------------------------------------------------------

    public function test_first_visit_sends_one_batch_and_a_feed_cursor(): void
    {
        Quote::factory()->published()->count(QuoteFeedService::BATCH_SIZE + 5)->create();

        $this->get(route('home'))
            ->assertInertia(fn ($page) => $page
                ->has('quotes', QuoteFeedService::BATCH_SIZE)
                ->where('quoteFeed.page', 1)
                ->where('quoteFeed.hasMore', true)
                ->where('quoteFeed.seed', fn ($seed) => is_int($seed) && $seed >= 1)
            );
    }

    public function test_featured_quotes_come_first(): void
    {
        Quote::factory()->published()->count(5)->create();
        $featured = Quote::factory()->published()->featured()->count(2)->create();

        $quotes = $this->get(route('home'))->viewData('page')['props']['quotes'];

        $this->assertEqualsCanonicalizing($featured->pluck('id')->all(), array_column(array_slice($quotes, 0, 2), 'id'));
    }

    public function test_later_batches_continue_the_same_shuffle_without_repeats(): void
    {
        $all = Quote::factory()->published()->count(QuoteFeedService::BATCH_SIZE + 5)->create();

        $firstPage = $this->get(route('home'))->viewData('page')['props'];
        $seed = $firstPage['quoteFeed']['seed'];

        $secondPage = $this->get(route('home', ['seed' => $seed, 'page' => 2]))->viewData('page')['props'];

        $this->assertCount(5, $secondPage['quotes']);
        $this->assertFalse($secondPage['quoteFeed']['hasMore']);

        $seenIds = [...array_column($firstPage['quotes'], 'id'), ...array_column($secondPage['quotes'], 'id')];
        $this->assertCount(count($all), array_unique($seenIds));
        $this->assertEqualsCanonicalizing($all->pluck('id')->all(), $seenIds);
    }

    public function test_the_same_seed_always_gives_the_same_order(): void
    {
        Quote::factory()->published()->count(10)->create();

        $first = $this->get(route('home', ['seed' => 12345]))->viewData('page')['props']['quotes'];
        $second = $this->get(route('home', ['seed' => 12345]))->viewData('page')['props']['quotes'];

        $this->assertSame(array_column($first, 'id'), array_column($second, 'id'));
    }

    public function test_quotes_are_merged_rather_than_replaced_on_partial_reloads(): void
    {
        Quote::factory()->published()->create();

        $page = $this->get(route('home'))->viewData('page');

        $this->assertContains('quotes', $page['mergeProps']);
        $this->assertContains('quotes.id', $page['matchPropsOn']);
    }

    public function test_mangled_feed_parameters_still_show_quotes(): void
    {
        Quote::factory()->published()->count(3)->create();

        $this->get('/?seed=-5&page=abc')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('quotes', 3)
                ->where('quoteFeed.page', 1)
            );
    }

    public function test_only_the_speaker_name_is_sent_with_each_quote(): void
    {
        Quote::factory()->published()->create();

        $this->get(route('home'))
            ->assertInertia(fn ($page) => $page
                ->has('quotes.0.speaker', fn ($speaker) => $speaker->has('id')->has('name'))
            );
    }

    public function test_occurred_at_is_sent_as_a_plain_calendar_date(): void
    {
        Quote::factory()->published()->create(['occurred_at' => '2024-01-15']);

        $this->get(route('home'))
            ->assertInertia(fn ($page) => $page
                ->where('quotes.0.occurred_at', '2024-01-15')
            );
    }
}
