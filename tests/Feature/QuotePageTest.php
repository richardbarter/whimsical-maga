<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\Speaker;
use App\Models\Subject;
use App\Models\SubjectSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuotePageTest extends TestCase
{
    use RefreshDatabase;

    private function pageUrl(Quote $quote): string
    {
        return route('quotes.show', [$quote, $quote->slug]);
    }

    // -------------------------------------------------------------------------
    // Visibility
    // -------------------------------------------------------------------------

    public function test_guest_can_view_a_published_quote(): void
    {
        $quote = Quote::factory()->published()->create([
            'context' => 'Said at a rally.',
            'claim' => 'The claim.',
            'reality_check' => 'The reality.',
        ]);

        $this->get($this->pageUrl($quote))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Quotes/Show')
                ->where('quote.id', $quote->id)
                ->where('quote.text', $quote->text)
                ->where('quote.context', 'Said at a rally.')
                ->where('quote.claim', 'The claim.')
                ->where('quote.reality_check', 'The reality.')
                ->where('quote.speaker.name', $quote->speaker->name)
                ->missing('quote.user_id')
                ->where('isPreview', false)
                ->has('quoteTypes')
                ->has('sourceTypes')
            );
    }

    public function test_logged_in_non_admin_can_view_a_published_quote(): void
    {
        $quote = Quote::factory()->published()->create();

        $this->actingAs(User::factory()->asUser()->create())
            ->get($this->pageUrl($quote))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('isPreview', false));
    }

    public function test_unpublished_quotes_are_not_found_for_guests(): void
    {
        $draft = Quote::factory()->create(['status' => QuoteStatus::Draft]);
        $pending = Quote::factory()->create(['status' => QuoteStatus::Pending]);

        $this->get($this->pageUrl($draft))->assertNotFound();
        $this->get($this->pageUrl($pending))->assertNotFound();
    }

    public function test_unpublished_quotes_are_not_found_for_non_admins(): void
    {
        $user = User::factory()->asUser()->create();
        $draft = Quote::factory()->create(['status' => QuoteStatus::Draft]);
        $pending = Quote::factory()->create(['status' => QuoteStatus::Pending]);

        $this->actingAs($user)->get($this->pageUrl($draft))->assertNotFound();
        $this->actingAs($user)->get($this->pageUrl($pending))->assertNotFound();
    }

    public function test_unpublished_quote_is_not_redirected_for_guests_with_a_wrong_slug(): void
    {
        $draft = Quote::factory()->create(['status' => QuoteStatus::Draft]);

        $this->get(route('quotes.show', [$draft, 'wrong-slug']))->assertNotFound();
    }

    public function test_admin_can_preview_unpublished_quotes(): void
    {
        $admin = User::factory()->admin()->create();
        $draft = Quote::factory()->create(['status' => QuoteStatus::Draft]);
        $pending = Quote::factory()->create(['status' => QuoteStatus::Pending]);

        $this->actingAs($admin)->get($this->pageUrl($draft))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Quotes/Show')
                ->where('isPreview', true)
            );

        $this->actingAs($admin)->get($this->pageUrl($pending))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('isPreview', true));
    }

    public function test_admin_viewing_a_published_quote_is_not_a_preview(): void
    {
        $quote = Quote::factory()->published()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get($this->pageUrl($quote))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('isPreview', false));
    }

    public function test_trashed_quote_is_not_found(): void
    {
        $quote = Quote::factory()->published()->create();
        $url = $this->pageUrl($quote);
        $quote->delete();

        $this->get($url)->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertNotFound();
    }

    public function test_unknown_or_non_numeric_id_is_not_found(): void
    {
        $this->get('/quotes/999999/some-slug')->assertNotFound();
        $this->get('/quotes/999999')->assertNotFound();
        $this->get('/quotes/not-a-number/some-slug')->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // Canonical URL
    // -------------------------------------------------------------------------

    public function test_missing_slug_redirects_permanently_to_the_canonical_url(): void
    {
        $quote = Quote::factory()->published()->create();

        $this->get("/quotes/{$quote->id}")
            ->assertStatus(301)
            ->assertRedirect($this->pageUrl($quote));
    }

    public function test_wrong_slug_redirects_permanently_to_the_canonical_url(): void
    {
        $quote = Quote::factory()->published()->create();

        $this->get("/quotes/{$quote->id}/an-outdated-slug")
            ->assertStatus(301)
            ->assertRedirect($this->pageUrl($quote));
    }

    public function test_old_url_redirects_to_the_new_slug_after_the_text_is_edited(): void
    {
        $admin = User::factory()->admin()->create();
        $quote = Quote::factory()->published()->create(['text' => 'The original words of the quote']);
        $oldUrl = $this->pageUrl($quote);

        $this->actingAs($admin)->put(route('admin.quotes.update', $quote), [
            'text' => 'Completely different words after an edit',
            'speaker' => $quote->speaker->name,
            'status' => 'published',
            'quote_type' => 'spoken',
            'is_verified' => false,
            'is_featured' => false,
        ])->assertRedirect(route('admin.quotes.index'));

        $quote->refresh();
        $this->assertNotSame($oldUrl, $this->pageUrl($quote));

        auth()->logout();

        $this->get($oldUrl)
            ->assertStatus(301)
            ->assertRedirect($this->pageUrl($quote));

        $this->get($this->pageUrl($quote))->assertOk();
    }

    // -------------------------------------------------------------------------
    // Sharing tags (server-rendered <head>)
    // -------------------------------------------------------------------------

    public function test_html_response_contains_sharing_tags(): void
    {
        $quote = Quote::factory()->published()->create([
            'text' => 'Nobody knows more about sharing tags than me',
            'speaker_id' => Speaker::factory()->create(['name' => 'Jane Speaker'])->id,
            'context' => 'Said while explaining Open Graph.',
        ]);
        $url = $this->pageUrl($quote);

        $this->get($url)
            ->assertOk()
            ->assertSee('<meta property="og:title" content="“Nobody knows more about sharing tags than me” — Jane Speaker">', false)
            ->assertSee('<meta name="description" content="Said while explaining Open Graph.">', false)
            ->assertSee('<meta property="og:description" content="Said while explaining Open Graph.">', false)
            ->assertSee('<meta property="og:url" content="'.$url.'">', false)
            ->assertSee('<link rel="canonical" href="'.$url.'">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta property="og:site_name" content="'.e(config('app.name')).'">', false)
            ->assertSee('<meta name="twitter:card" content="summary">', false);
    }

    public function test_description_falls_back_to_the_reality_check_then_the_app_name(): void
    {
        $withRealityCheck = Quote::factory()->published()->create([
            'context' => null,
            'reality_check' => str_repeat('The facts say otherwise. ', 20),
        ]);
        $withNothing = Quote::factory()->published()->create(['context' => null, 'reality_check' => null]);

        $response = $this->get($this->pageUrl($withRealityCheck))->assertOk();
        preg_match('/<meta name="description" content="([^"]*)">/', $response->getContent(), $matches);
        $this->assertStringStartsWith('The facts say otherwise.', $matches[1]);
        $this->assertLessThanOrEqual(161, mb_strlen(html_entity_decode($matches[1])));

        $this->get($this->pageUrl($withNothing))
            ->assertSee('<meta name="description" content="'.e(config('app.name')).'">', false);
    }

    public function test_quote_text_is_escaped_in_the_html_response(): void
    {
        $quote = Quote::factory()->published()->create([
            'text' => 'Break out "<script>alert(1)</script> of the tag',
            'context' => '"><script>alert(2)</script>',
        ]);

        $this->get($this->pageUrl($quote))
            ->assertOk()
            ->assertDontSee('<script>alert(', false)
            ->assertSee('&quot;&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('<meta name="description" content="&quot;&gt;&lt;script&gt;alert(2)&lt;/script&gt;">', false);
    }

    public function test_other_pages_have_no_sharing_tags(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('og:title', false);
    }

    // -------------------------------------------------------------------------
    // Subjects
    // -------------------------------------------------------------------------

    public function test_guests_and_non_admins_only_get_published_subjects(): void
    {
        $quote = Quote::factory()->published()->create();
        $published = Subject::factory()->published()->create();
        $draft = Subject::factory()->create();
        $quote->subjects()->attach([$draft->id => ['position' => 0], $published->id => ['position' => 1]]);

        $this->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page
                ->has('subjects', 1)
                ->where('subjects.0.id', $published->id)
            );

        $this->actingAs(User::factory()->asUser()->create())
            ->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page->has('subjects', 1));
    }

    public function test_admin_also_gets_draft_subjects(): void
    {
        $quote = Quote::factory()->published()->create();
        $quote->subjects()->attach([
            Subject::factory()->create()->id => ['position' => 0],
            Subject::factory()->published()->create()->id => ['position' => 1],
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page
                ->has('subjects', 2)
                ->where('subjects.0.status', 'draft')
                ->where('subjects.1.status', 'published')
            );
    }

    public function test_subjects_are_in_position_order(): void
    {
        $quote = Quote::factory()->published()->create();
        $first = Subject::factory()->published()->create(['name' => 'Zebra comes first']);
        $second = Subject::factory()->published()->create(['name' => 'Apple comes second']);
        $third = Subject::factory()->published()->create(['name' => 'Mango comes third']);
        $quote->subjects()->attach([
            $third->id => ['position' => 2],
            $first->id => ['position' => 0],
            $second->id => ['position' => 1],
        ]);

        $this->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page
                ->where('subjects.0.id', $first->id)
                ->where('subjects.1.id', $second->id)
                ->where('subjects.2.id', $third->id)
            );
    }

    public function test_soft_deleted_subjects_are_hidden_even_from_admins(): void
    {
        $quote = Quote::factory()->published()->create();
        $subject = Subject::factory()->published()->create();
        $quote->subjects()->attach($subject);
        $subject->delete();

        $this->get($this->pageUrl($quote))->assertInertia(fn ($page) => $page->has('subjects', 0));
        $this->actingAs(User::factory()->admin()->create())
            ->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page->has('subjects', 0));
    }

    public function test_subjects_send_only_the_page_fields(): void
    {
        $quote = Quote::factory()->published()->create();
        $subject = Subject::factory()->published()->create(['full_details' => '<p>Explained</p>']);
        $quote->subjects()->attach($subject);

        $this->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page
                ->has('subjects.0', fn ($json) => $json
                    ->where('id', $subject->id)
                    ->where('name', $subject->name)
                    ->where('slug', $subject->slug)
                    ->where('status', 'published')
                    ->where('full_details_html', '<p>Explained</p>')
                    ->has('sources', 0)
                )
                ->missing('quote.subjects')
            );
    }

    public function test_full_details_written_straight_to_the_database_are_sanitized_on_display(): void
    {
        $quote = Quote::factory()->published()->create();
        $subject = Subject::factory()->published()->create();
        $quote->subjects()->attach($subject);

        // Bypasses SubjectService (as a seeder, tinker or importer could)
        Subject::whereKey($subject->id)->update([
            'full_details' => '<p onclick="steal()">Hi</p><script>alert(1)</script><img src=x onerror="alert(2)">'
                .'<p><a href="javascript:alert(3)">link</a></p>',
        ]);

        $this->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page->where(
                'subjects.0.full_details_html',
                '<p>Hi</p><p><a rel="nofollow noopener noreferrer" target="_blank">link</a></p>'
            ));
    }

    public function test_subject_images_are_shown_only_from_our_storage(): void
    {
        $quote = Quote::factory()->published()->create();
        $subject = Subject::factory()->published()->create();
        $quote->subjects()->attach($subject);
        $ours = Storage::url('subject-images/chart.png');

        Subject::whereKey($subject->id)->update([
            'full_details' => '<figure><img src="'.$ours.'" alt="Chart"></figure><p><img src="https://evil.example/pixel.gif" alt=""></p><p>Text</p>',
        ]);

        $this->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page->where(
                'subjects.0.full_details_html',
                '<figure><img src="'.$ours.'" alt="Chart" loading="lazy" /></figure><p></p><p>Text</p>'
            ));
    }

    public function test_each_subject_sends_its_sources_in_order(): void
    {
        $quote = Quote::factory()->published()->create();
        $subject = Subject::factory()->published()->create();
        $quote->subjects()->attach($subject);
        SubjectSource::factory()->for($subject)->create([
            'url' => 'https://example.com/second', 'position' => 1, 'title' => null, 'source_type' => null, 'archived_url' => null,
        ]);
        SubjectSource::factory()->for($subject)->create([
            'url' => 'https://example.com/first', 'position' => 0, 'title' => 'The study', 'source_type' => 'study',
            'archived_url' => 'https://web.archive.org/web/2024/https://example.com/first',
        ]);

        $this->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page
                ->has('subjects.0.sources', 2)
                ->has('subjects.0.sources.0', fn ($source) => $source
                    ->where('url', 'https://example.com/first')
                    ->where('title', 'The study')
                    ->where('source_type', 'study')
                    ->where('archived_url', 'https://web.archive.org/web/2024/https://example.com/first')
                )
                ->where('subjects.0.sources.1.url', 'https://example.com/second')
                ->has('subjectSourceTypes', 9)
            );
    }

    public function test_a_draft_subjects_sources_are_hidden_from_guests_with_it(): void
    {
        $quote = Quote::factory()->published()->create();
        $draft = Subject::factory()->create();
        $quote->subjects()->attach($draft);
        SubjectSource::factory()->for($draft)->create(['url' => 'https://example.com/draft-evidence']);

        $this->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page->has('subjects', 0))
            ->assertDontSee('draft-evidence');

        $this->actingAs(User::factory()->admin()->create())
            ->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page->where('subjects.0.sources.0.url', 'https://example.com/draft-evidence'));
    }

    public function test_a_subject_without_full_details_sends_null(): void
    {
        $quote = Quote::factory()->published()->create();
        $quote->subjects()->attach(Subject::factory()->published()->withoutDetails()->create());

        $this->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page->where('subjects.0.full_details_html', null));
    }

    public function test_a_quote_without_subjects_sends_an_empty_list(): void
    {
        $quote = Quote::factory()->published()->create();

        $this->get($this->pageUrl($quote))->assertInertia(fn ($page) => $page->has('subjects', 0));
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function test_sources_are_ordered_primary_first(): void
    {
        $quote = Quote::factory()->published()->create();
        $quote->sources()->create(['url' => 'https://example.com/first', 'is_primary' => false]);
        $quote->sources()->create(['url' => 'https://example.com/primary', 'is_primary' => true]);
        $quote->sources()->create(['url' => 'https://example.com/third', 'is_primary' => false]);

        $this->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page
                ->has('quote.sources', 3)
                ->where('quote.sources.0.url', 'https://example.com/primary')
                ->where('quote.sources.1.url', 'https://example.com/first')
                ->where('quote.sources.2.url', 'https://example.com/third')
            );
    }

    public function test_page_includes_tags_and_categories(): void
    {
        $quote = Quote::factory()->published()->create();
        $quote->tags()->create(['name' => 'Elections']);
        $quote->categories()->create(['name' => 'Voting']);

        $this->get($this->pageUrl($quote))
            ->assertInertia(fn ($page) => $page
                ->has('quote.tags', 1)
                ->where('quote.tags.0.name', 'Elections')
                ->has('quote.categories', 1)
                ->where('quote.categories.0.name', 'Voting')
            );
    }
}
