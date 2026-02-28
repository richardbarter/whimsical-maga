<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Quote;
use App\Models\Speaker;
use App\Models\SpeakerAlias;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->regularUser = User::factory()->asUser()->create();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'text' => 'This is a test quote for the admin panel',
            'speaker' => 'Donald Trump',
            'status' => 'draft',
            'quote_type' => 'spoken',
            'is_verified' => false,
            'is_featured' => false,
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_is_redirected_to_login_for_all_quote_routes(): void
    {
        $quote = Quote::factory()->create();

        $this->get(route('admin.quotes.index'))->assertRedirect(route('login'));
        $this->get(route('admin.quotes.create'))->assertRedirect(route('login'));
        $this->post(route('admin.quotes.store'))->assertRedirect(route('login'));
        $this->get(route('admin.quotes.edit', $quote))->assertRedirect(route('login'));
        $this->put(route('admin.quotes.update', $quote))->assertRedirect(route('login'));
        $this->delete(route('admin.quotes.destroy', $quote))->assertRedirect(route('login'));
        $this->patch(route('admin.quotes.verify', $quote))->assertRedirect(route('login'));
        $this->patch(route('admin.quotes.feature', $quote))->assertRedirect(route('login'));
    }

    public function test_non_admin_user_receives_403_for_all_quote_routes(): void
    {
        $quote = Quote::factory()->create();

        $this->actingAs($this->regularUser)->get(route('admin.quotes.index'))->assertForbidden();
        $this->actingAs($this->regularUser)->get(route('admin.quotes.create'))->assertForbidden();
        $this->actingAs($this->regularUser)->post(route('admin.quotes.store'))->assertForbidden();
        $this->actingAs($this->regularUser)->get(route('admin.quotes.edit', $quote))->assertForbidden();
        $this->actingAs($this->regularUser)->put(route('admin.quotes.update', $quote))->assertForbidden();
        $this->actingAs($this->regularUser)->delete(route('admin.quotes.destroy', $quote))->assertForbidden();
        $this->actingAs($this->regularUser)->patch(route('admin.quotes.verify', $quote))->assertForbidden();
        $this->actingAs($this->regularUser)->patch(route('admin.quotes.feature', $quote))->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_admin_can_view_quotes_index(): void
    {
        Quote::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.quotes.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Quotes/Index')
            ->has('quotes.data', 3)
        );
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_admin_can_view_create_quote_form(): void
    {
        Tag::factory()->count(2)->create();
        Category::factory()->count(2)->create();
        Speaker::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.quotes.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Quotes/Create')
            ->has('tags', 2)
            ->has('categories', 2)
            ->has('speakers', 2)
            ->has('quoteTypes', 6)
        );
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function test_admin_can_create_a_quote(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload());

        $response->assertRedirect(route('admin.quotes.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('quotes', 1);
    }

    public function test_store_creates_new_speaker_when_name_not_found(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload(['speaker' => 'Brand New Speaker']));

        $this->assertDatabaseHas('speakers', ['name' => 'Brand New Speaker']);
    }

    public function test_store_resolves_speaker_by_name_case_insensitively(): void
    {
        $speaker = Speaker::factory()->create(['name' => 'Donald Trump']);

        $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload(['speaker' => 'donald trump']));

        $this->assertDatabaseCount('speakers', 1);
        $this->assertDatabaseHas('quotes', ['speaker_id' => $speaker->id]);
    }

    public function test_store_resolves_existing_speaker_by_exact_name(): void
    {
        $speaker = Speaker::factory()->create(['name' => 'Donald Trump']);

        $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload(['speaker' => 'Donald Trump']));

        $this->assertDatabaseCount('speakers', 1);
        $this->assertDatabaseHas('quotes', ['speaker_id' => $speaker->id]);
    }

    public function test_store_resolves_speaker_by_alias(): void
    {
        $speaker = Speaker::factory()->create(['name' => 'Donald Trump']);
        SpeakerAlias::create(['speaker_id' => $speaker->id, 'alias' => 'The Donald']);

        $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload(['speaker' => 'The Donald']));

        $this->assertDatabaseCount('speakers', 1);
        $this->assertDatabaseHas('quotes', ['speaker_id' => $speaker->id]);
    }

    public function test_store_generates_slug_from_first_eight_words_of_text(): void
    {
        $text = 'one two three four five six seven eight nine ten';

        $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload(['text' => $text]));

        $this->assertDatabaseHas('quotes', ['slug' => 'one-two-three-four-five-six-seven-eight']);
    }

    public function test_store_sets_published_at_when_status_is_published(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload(['status' => 'published']));

        $this->assertNotNull(Quote::first()->published_at);
    }

    public function test_store_appends_counter_to_slug_on_collision(): void
    {
        Quote::factory()->create(['slug' => 'one-two-three-four-five-six-seven-eight']);

        $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload([
                'text' => 'one two three four five six seven eight different ending',
            ]));

        $this->assertDatabaseHas('quotes', ['slug' => 'one-two-three-four-five-six-seven-eight-1']);
    }

    public function test_store_does_not_set_published_at_for_draft_status(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload(['status' => 'draft']));

        $this->assertNull(Quote::first()->published_at);
    }

    public function test_store_syncs_tags_categories_and_sources(): void
    {
        $tag = Tag::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.quotes.store'), $this->validPayload([
            'tags' => [(string) $tag->id],
            'categories' => [(string) $category->id],
            'sources' => [
                ['url' => 'https://example.com', 'title' => 'Test', 'source_type' => 'article', 'is_primary' => true],
            ],
        ]));

        $quote = Quote::with(['tags', 'categories', 'sources'])->first();
        $this->assertTrue($quote->tags->contains($tag));
        $this->assertTrue($quote->categories->contains($category));
        $this->assertCount(1, $quote->sources);
        $this->assertEquals('https://example.com', $quote->sources->first()->url);
        $this->assertTrue($quote->sources->first()->is_primary);
    }

    public function test_store_can_create_new_tags_by_name(): void
    {
        $this->actingAs($this->admin)->post(route('admin.quotes.store'), $this->validPayload([
            'tags' => ['brand-new-tag'],
        ]));

        $this->assertDatabaseHas('tags', ['name' => 'brand-new-tag']);
        $this->assertCount(1, Quote::first()->tags);
    }

    public function test_store_validation_fails_when_required_fields_are_missing(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), []);

        $response->assertSessionHasErrors(['text', 'speaker', 'status', 'quote_type']);
    }

    public function test_store_validation_fails_with_invalid_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload(['status' => 'invalid']));

        $response->assertSessionHasErrors(['status']);
    }

    public function test_store_validation_fails_with_invalid_quote_type(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.quotes.store'), $this->validPayload(['quote_type' => 'shouted']));

        $response->assertSessionHasErrors(['quote_type']);
    }

    public function test_store_validation_fails_with_invalid_source_url(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.quotes.store'), $this->validPayload([
            'sources' => [['url' => 'not-a-url', 'title' => null, 'source_type' => null, 'is_primary' => false]],
        ]));

        $response->assertSessionHasErrors(['sources.0.url']);
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function test_admin_can_view_edit_form_with_quote_data(): void
    {
        $quote = Quote::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.quotes.edit', $quote));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Quotes/Edit')
            ->has('quote')
            ->where('quote.id', $quote->id)
            ->has('quoteTypes', 6)
        );
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function test_admin_can_update_a_quote(): void
    {
        $quote = Quote::factory()->create();

        $response = $this->actingAs($this->admin)->put(
            route('admin.quotes.update', $quote),
            $this->validPayload(['text' => $quote->text, 'status' => 'draft', 'quote_type' => 'written'])
        );

        $response->assertRedirect(route('admin.quotes.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'quote_type' => 'written']);
    }

    public function test_update_does_not_overwrite_published_at_on_republish(): void
    {
        $original = now()->subDays(5);
        $quote = Quote::factory()->create(['status' => 'published', 'published_at' => $original]);

        $this->actingAs($this->admin)->put(
            route('admin.quotes.update', $quote),
            $this->validPayload(['text' => $quote->text, 'status' => 'published'])
        );

        $this->assertEquals(
            $original->toDateTimeString(),
            $quote->fresh()->published_at->toDateTimeString()
        );
    }

    public function test_update_does_not_regenerate_slug_when_text_is_unchanged(): void
    {
        $quote = Quote::factory()->create();
        $originalSlug = $quote->slug;

        $this->actingAs($this->admin)
            ->put(route('admin.quotes.update', $quote), $this->validPayload(['text' => $quote->text]));

        $this->assertEquals($originalSlug, $quote->fresh()->slug);
    }

    public function test_update_regenerates_slug_when_text_changes(): void
    {
        $quote = Quote::factory()->create();
        $originalSlug = $quote->slug;

        $this->actingAs($this->admin)->put(route('admin.quotes.update', $quote), $this->validPayload([
            'text' => 'totally different text that is brand new here indeed',
        ]));

        $this->assertNotEquals($originalSlug, $quote->fresh()->slug);
        $this->assertEquals('totally-different-text-that-is-brand-new-here', $quote->fresh()->slug);
    }

    public function test_update_validation_fails_when_required_fields_are_missing(): void
    {
        $quote = Quote::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('admin.quotes.update', $quote), []);

        $response->assertSessionHasErrors(['text', 'speaker', 'status', 'quote_type']);
    }

    public function test_update_syncs_sources_with_is_primary(): void
    {
        $quote = Quote::factory()->create();

        $this->actingAs($this->admin)->put(
            route('admin.quotes.update', $quote),
            $this->validPayload([
                'text' => $quote->text,
                'sources' => [
                    ['url' => 'https://primary.com', 'title' => 'Primary', 'source_type' => 'article', 'is_primary' => true, 'archived_url' => ''],
                    ['url' => 'https://secondary.com', 'title' => 'Secondary', 'source_type' => 'article', 'is_primary' => false, 'archived_url' => ''],
                ],
            ])
        );

        $sources = $quote->fresh()->sources;
        $this->assertCount(2, $sources);
        $this->assertTrue($sources->firstWhere('url', 'https://primary.com')->is_primary);
        $this->assertFalse($sources->firstWhere('url', 'https://secondary.com')->is_primary);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function test_admin_can_delete_a_quote(): void
    {
        $quote = Quote::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.quotes.destroy', $quote));

        $response->assertRedirect(route('admin.quotes.index'));
        $response->assertSessionHas('success');
    }

    public function test_delete_soft_deletes_the_quote(): void
    {
        $quote = Quote::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.quotes.destroy', $quote));

        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);
        $this->assertDatabaseCount('quotes', 1); // row still exists
    }

    // -------------------------------------------------------------------------
    // Toggle Verified
    // -------------------------------------------------------------------------

    public function test_toggle_verified_sets_is_verified_to_true(): void
    {
        $quote = Quote::factory()->create(['is_verified' => false]);

        $this->actingAs($this->admin)->patch(route('admin.quotes.verify', $quote));

        $this->assertTrue($quote->fresh()->is_verified);
    }

    public function test_toggle_verified_sets_is_verified_to_false(): void
    {
        $quote = Quote::factory()->verified()->create();

        $this->actingAs($this->admin)->patch(route('admin.quotes.verify', $quote));

        $this->assertFalse($quote->fresh()->is_verified);
    }

    // -------------------------------------------------------------------------
    // Toggle Feature
    // -------------------------------------------------------------------------

    public function test_toggle_feature_sets_is_featured_to_true(): void
    {
        $quote = Quote::factory()->create(['is_featured' => false]);

        $this->actingAs($this->admin)->patch(route('admin.quotes.feature', $quote));

        $this->assertTrue($quote->fresh()->is_featured);
    }

    public function test_toggle_feature_sets_is_featured_to_false(): void
    {
        $quote = Quote::factory()->featured()->create();

        $this->actingAs($this->admin)->patch(route('admin.quotes.feature', $quote));

        $this->assertFalse($quote->fresh()->is_featured);
    }
}
