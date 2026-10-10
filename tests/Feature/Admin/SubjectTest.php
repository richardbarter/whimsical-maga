<?php

namespace Tests\Feature\Admin;

use App\Enums\SubjectSourceType;
use App\Enums\SubjectStatus;
use App\Models\Quote;
use App\Models\Subject;
use App\Models\SubjectSource;
use App\Models\User;
use App\Services\RichTextSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectTest extends TestCase
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
            'name' => 'Mail-in ballots are rife with fraud',
            'status' => 'draft',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_is_redirected_to_login_for_all_subject_routes(): void
    {
        $subject = Subject::factory()->create();

        $this->get(route('admin.subjects.index'))->assertRedirect(route('login'));
        $this->get(route('admin.subjects.create'))->assertRedirect(route('login'));
        $this->post(route('admin.subjects.store'))->assertRedirect(route('login'));
        $this->get(route('admin.subjects.edit', $subject))->assertRedirect(route('login'));
        $this->put(route('admin.subjects.update', $subject))->assertRedirect(route('login'));
        $this->delete(route('admin.subjects.destroy', $subject))->assertRedirect(route('login'));
    }

    public function test_non_admin_user_receives_403_for_all_subject_routes(): void
    {
        $subject = Subject::factory()->create();

        $this->actingAs($this->regularUser)->get(route('admin.subjects.index'))->assertForbidden();
        $this->actingAs($this->regularUser)->get(route('admin.subjects.create'))->assertForbidden();
        $this->actingAs($this->regularUser)->post(route('admin.subjects.store'))->assertForbidden();
        $this->actingAs($this->regularUser)->get(route('admin.subjects.edit', $subject))->assertForbidden();
        $this->actingAs($this->regularUser)->put(route('admin.subjects.update', $subject))->assertForbidden();
        $this->actingAs($this->regularUser)->delete(route('admin.subjects.destroy', $subject))->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_admin_can_view_subjects_index(): void
    {
        Subject::factory()->count(3)->create();

        $this->actingAs($this->admin)->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Subjects/Index')
                ->has('subjects.data', 3)
            );
    }

    public function test_index_counts_quotes_and_leaves_out_full_details(): void
    {
        $subject = Subject::factory()->create();
        $subject->quotes()->attach(Quote::factory()->count(2)->create());
        $trashedQuote = Quote::factory()->create();
        $subject->quotes()->attach($trashedQuote);
        $trashedQuote->delete();

        $this->actingAs($this->admin)->get(route('admin.subjects.index'))
            ->assertInertia(fn ($page) => $page
                ->where('subjects.data.0.quotes_count', 2)
                ->where('subjects.data.0.status', 'draft')
                ->missing('subjects.data.0.full_details')
            );
    }

    public function test_index_excludes_trashed_subjects(): void
    {
        Subject::factory()->create();
        Subject::factory()->create()->delete();

        $this->actingAs($this->admin)->get(route('admin.subjects.index'))
            ->assertInertia(fn ($page) => $page->has('subjects.data', 1));
    }

    // -------------------------------------------------------------------------
    // Create & store
    // -------------------------------------------------------------------------

    public function test_admin_can_view_create_subject_form(): void
    {
        $this->actingAs($this->admin)->get(route('admin.subjects.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Subjects/Create')
                ->has('subjectStatuses', 2)
                ->has('sourceTypes', 9)
                ->where('imageUrlPrefix', Storage::url('subject-images/'))
            );
    }

    public function test_admin_can_create_a_subject(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload(['status' => 'published']))
            ->assertRedirect(route('admin.subjects.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseHas('subjects', [
            'name' => 'Mail-in ballots are rife with fraud',
            'slug' => 'mail-in-ballots-are-rife-with-fraud',
            'status' => 'published',
            'full_details' => null,
        ]);
    }

    public function test_store_ignores_a_submitted_slug(): void
    {
        $this->actingAs($this->admin)->post(route('admin.subjects.store'), $this->validPayload([
            'slug' => 'chosen-slug',
        ]));

        $this->assertDatabaseHas('subjects', ['slug' => 'mail-in-ballots-are-rife-with-fraud']);
    }

    // -------------------------------------------------------------------------
    // Full details
    // -------------------------------------------------------------------------

    public function test_store_saves_sanitized_full_details(): void
    {
        $this->actingAs($this->admin)->post(route('admin.subjects.store'), $this->validPayload([
            'full_details' => '<h3>Heading</h3><p onclick="steal()">Safe <strong>text</strong></p><script>alert(1)</script>'
                .'<p><a href="javascript:alert(1)">bad link</a> <a href="https://example.com">good link</a></p>',
        ]))->assertRedirect(route('admin.subjects.index'));

        $this->assertSame(
            '<h3>Heading</h3><p>Safe <strong>text</strong></p>'
                .'<p><a rel="nofollow noopener noreferrer" target="_blank">bad link</a> '
                .'<a href="https://example.com" rel="nofollow noopener noreferrer" target="_blank">good link</a></p>',
            Subject::first()->full_details
        );
    }

    public function test_update_saves_sanitized_full_details(): void
    {
        $subject = Subject::factory()->create(['full_details' => '<p>Old</p>']);

        $this->actingAs($this->admin)->put(route('admin.subjects.update', $subject), $this->validPayload([
            'full_details' => '<p>New <img src="x" onerror="alert(1)"></p><iframe src="https://evil.example"></iframe>',
        ]))->assertRedirect(route('admin.subjects.index'));

        $this->assertSame('<p>New </p>', $subject->fresh()->full_details);
    }

    public function test_full_details_keep_our_images_and_drop_others(): void
    {
        $ours = Storage::url('subject-images/chart.png');

        $this->actingAs($this->admin)->post(route('admin.subjects.store'), $this->validPayload([
            'full_details' => '<p>Intro</p>'
                .'<figure><img src="'.$ours.'" alt="Chart" width="800" height="600"><figcaption>Chart — Source</figcaption></figure>'
                .'<figure><img src="https://evil.example/tracker.png" alt="x"><figcaption>Theirs</figcaption></figure>',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(
            '<p>Intro</p><figure><img src="'.$ours.'" alt="Chart" width="800" height="600" loading="lazy" /><figcaption>Chart — Source</figcaption></figure>',
            Subject::first()->full_details
        );
    }

    public function test_empty_editor_content_is_saved_as_null(): void
    {
        $subject = Subject::factory()->create(['full_details' => '<p>Written earlier</p>']);

        foreach (['<p></p>', '', '<p><br></p>'] as $emptyContent) {
            $subject->update(['full_details' => '<p>Written earlier</p>']);

            $this->actingAs($this->admin)->put(route('admin.subjects.update', $subject), $this->validPayload([
                'full_details' => $emptyContent,
            ]))->assertSessionHasNoErrors();

            $this->assertNull($subject->fresh()->full_details, "Content: {$emptyContent}");
        }
    }

    public function test_full_details_can_be_as_long_as_the_limit(): void
    {
        $html = '<p>'.str_repeat('a', RichTextSanitizer::MAX_LENGTH - 7).'</p>';

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload(['full_details' => $html]))
            ->assertSessionHasNoErrors();

        $this->assertSame($html, Subject::first()->full_details);
    }

    public function test_full_details_longer_than_the_limit_are_rejected(): void
    {
        $html = '<p>'.str_repeat('a', RichTextSanitizer::MAX_LENGTH - 6).'</p>';

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload(['full_details' => $html]))
            ->assertSessionHasErrors(['full_details' => 'The full details are too long to save (including formatting). Try splitting them across two subjects.']);

        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_full_details_must_be_a_string(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload(['full_details' => ['<p>array</p>']]))
            ->assertSessionHasErrors(['full_details']);
    }

    public function test_store_validation_requires_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload(['name' => '']))
            ->assertSessionHasErrors(['name' => 'The title field is required.']);

        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_store_validation_fails_when_name_exceeds_255_chars(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload(['name' => str_repeat('a', 256)]))
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_validation_requires_a_valid_status(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload(['status' => '']))
            ->assertSessionHasErrors(['status']);

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload(['status' => 'pending']))
            ->assertSessionHasErrors(['status']);

        $this->assertDatabaseCount('subjects', 0);
    }

    // -------------------------------------------------------------------------
    // Slugs
    // -------------------------------------------------------------------------

    public function test_store_gives_a_colliding_slug_a_suffix(): void
    {
        Subject::factory()->create(['name' => 'Mail-in ballots are rife with fraud']);

        $this->actingAs($this->admin)->post(route('admin.subjects.store'), $this->validPayload([
            'name' => 'Mail-in ballots are rife with fraud!',
        ]));

        $this->assertDatabaseHas('subjects', [
            'name' => 'Mail-in ballots are rife with fraud!',
            'slug' => 'mail-in-ballots-are-rife-with-fraud-1',
        ]);
    }

    public function test_store_slug_avoids_trashed_subjects(): void
    {
        Subject::factory()->create(['name' => 'Mail-in ballots are rife with fraud'])->delete();

        $this->actingAs($this->admin)->post(route('admin.subjects.store'), $this->validPayload());

        $this->assertDatabaseHas('subjects', [
            'deleted_at' => null,
            'slug' => 'mail-in-ballots-are-rife-with-fraud-1',
        ]);
    }

    public function test_update_regenerates_the_slug_when_the_name_changes(): void
    {
        $subject = Subject::factory()->create(['name' => 'Old title']);

        $this->actingAs($this->admin)->put(route('admin.subjects.update', $subject), $this->validPayload([
            'name' => 'New title',
        ]));

        $this->assertSame('new-title', $subject->fresh()->slug);
    }

    public function test_update_keeps_the_slug_when_the_name_is_unchanged(): void
    {
        $subject = Subject::factory()->create(['name' => 'Same title', 'slug' => 'a-custom-slug']);

        $this->actingAs($this->admin)->put(route('admin.subjects.update', $subject), $this->validPayload([
            'name' => 'Same title',
            'status' => 'published',
        ]));

        $this->assertSame('a-custom-slug', $subject->fresh()->slug);
        $this->assertSame(SubjectStatus::Published, $subject->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // Edit & update
    // -------------------------------------------------------------------------

    public function test_admin_can_view_edit_subject_form(): void
    {
        $subject = Subject::factory()->published()->create();

        $this->actingAs($this->admin)->get(route('admin.subjects.edit', $subject))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Subjects/Edit')
                ->where('subject.id', $subject->id)
                ->where('subject.name', $subject->name)
                ->where('subject.status', 'published')
                ->where('subject.full_details', $subject->full_details)
                ->has('subject.sources')
                ->has('subjectStatuses', 2)
                ->has('sourceTypes', 9)
                ->where('imageUrlPrefix', Storage::url('subject-images/'))
            );
    }

    public function test_edit_of_a_trashed_subject_is_not_found(): void
    {
        $subject = Subject::factory()->create();
        $subject->delete();

        $this->actingAs($this->admin)->get(route('admin.subjects.edit', $subject))->assertNotFound();
    }

    public function test_admin_can_update_a_subject(): void
    {
        $subject = Subject::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.subjects.update', $subject), $this->validPayload([
                'name' => 'Updated title',
                'status' => 'published',
            ]))
            ->assertRedirect(route('admin.subjects.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'name' => 'Updated title',
            'status' => 'published',
        ]);
    }

    public function test_update_keeps_existing_full_details(): void
    {
        $subject = Subject::factory()->create(['full_details' => '<p>Written earlier</p>']);

        $this->actingAs($this->admin)->put(route('admin.subjects.update', $subject), $this->validPayload());

        $this->assertSame('<p>Written earlier</p>', $subject->fresh()->full_details);
    }

    public function test_update_validation_errors(): void
    {
        $subject = Subject::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.subjects.update', $subject), ['name' => '', 'status' => 'nope'])
            ->assertSessionHasErrors(['name', 'status']);
    }

    // -------------------------------------------------------------------------
    // Sources
    // -------------------------------------------------------------------------

    public function test_store_saves_sources_in_the_submitted_order(): void
    {
        $this->actingAs($this->admin)->post(route('admin.subjects.store'), $this->validPayload([
            'sources' => [
                ['url' => 'https://example.com/study', 'title' => 'The study', 'source_type' => 'study', 'archived_url' => 'https://web.archive.org/web/2024/https://example.com/study'],
                ['url' => 'https://example.com/ruling', 'title' => null, 'source_type' => 'court_ruling', 'archived_url' => null],
            ],
        ]))->assertSessionHasNoErrors();

        $sources = Subject::first()->sources;
        $this->assertSame(['https://example.com/study', 'https://example.com/ruling'], $sources->pluck('url')->all());
        $this->assertSame([0, 1], $sources->pluck('position')->all());
        $this->assertSame(SubjectSourceType::Study, $sources[0]->source_type);
        $this->assertSame('The study', $sources[0]->title);
        $this->assertSame('https://web.archive.org/web/2024/https://example.com/study', $sources[0]->archived_url);
    }

    public function test_update_replaces_sources_wholesale_in_the_new_order(): void
    {
        $subject = Subject::factory()->create();
        SubjectSource::factory()->for($subject)->create(['url' => 'https://example.com/old', 'position' => 0]);
        SubjectSource::factory()->for($subject)->create(['url' => 'https://example.com/kept', 'position' => 1]);

        $this->actingAs($this->admin)->put(route('admin.subjects.update', $subject), $this->validPayload([
            'sources' => [
                ['url' => 'https://example.com/new'],
                ['url' => 'https://example.com/kept'],
            ],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['https://example.com/new', 'https://example.com/kept'], $subject->sources()->pluck('url')->all());
        $this->assertSame(2, SubjectSource::count());
    }

    public function test_an_empty_source_list_removes_them_all(): void
    {
        $subject = Subject::factory()->create();
        SubjectSource::factory()->for($subject)->count(2)->create();

        $this->actingAs($this->admin)->put(route('admin.subjects.update', $subject), $this->validPayload(['sources' => []]));

        $this->assertSame(0, $subject->sources()->count());
    }

    public function test_update_without_a_sources_field_keeps_them(): void
    {
        $subject = Subject::factory()->create();
        SubjectSource::factory()->for($subject)->count(2)->create();

        $this->actingAs($this->admin)->put(route('admin.subjects.update', $subject), $this->validPayload());

        $this->assertSame(2, $subject->sources()->count());
    }

    public function test_sources_ignore_fields_outside_the_form(): void
    {
        $otherSubject = Subject::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.subjects.store'), $this->validPayload([
            'sources' => [['url' => 'https://example.com', 'subject_id' => $otherSubject->id, 'position' => 99, 'is_primary' => true]],
        ]));

        $source = SubjectSource::sole();
        $this->assertNotSame($otherSubject->id, $source->subject_id);
        $this->assertSame(0, $source->position);
    }

    public function test_source_url_is_required_and_must_be_http_or_https(): void
    {
        foreach (['', 'not a url', 'javascript:alert(1)', 'ftp://example.com/file'] as $url) {
            $this->actingAs($this->admin)
                ->post(route('admin.subjects.store'), $this->validPayload(['sources' => [['url' => $url]]]))
                ->assertSessionHasErrors('sources.0.url');
        }

        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_source_urls_are_limited_to_2048_characters(): void
    {
        $long = 'https://example.com/'.str_repeat('a', 2029);
        $this->assertSame(2049, strlen($long));

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload([
                'sources' => [['url' => $long, 'archived_url' => $long]],
            ]))
            ->assertSessionHasErrors(['sources.0.url', 'sources.0.archived_url']);

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload([
                'sources' => [['url' => substr($long, 0, 2048)]],
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_source_type_must_be_a_subject_source_type(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload([
                // "rally" is a quote source type, not evidence
                'sources' => [['url' => 'https://example.com', 'source_type' => 'rally']],
            ]))
            ->assertSessionHasErrors(['sources.0.source_type' => 'The selected source type is invalid.']);
    }

    public function test_archived_url_must_be_http_or_https(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $this->validPayload([
                'sources' => [['url' => 'https://example.com', 'archived_url' => 'javascript:alert(1)']],
            ]))
            ->assertSessionHasErrors('sources.0.archived_url');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_visible_to_scope_shows_drafts_to_admins_only(): void
    {
        $published = Subject::factory()->published()->create();
        $draft = Subject::factory()->create();

        $this->assertEqualsCanonicalizing(
            [$published->id, $draft->id],
            Subject::visibleTo($this->admin)->pluck('id')->all()
        );
        $this->assertEquals([$published->id], Subject::visibleTo($this->regularUser)->pluck('id')->all());
        $this->assertEquals([$published->id], Subject::visibleTo(null)->pluck('id')->all());
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function test_admin_can_soft_delete_a_subject(): void
    {
        $subject = Subject::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.subjects.destroy', $subject))
            ->assertRedirect(route('admin.subjects.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($subject);
    }

    public function test_soft_deleted_subject_no_longer_appears_on_its_quotes(): void
    {
        $quote = Quote::factory()->create();
        $subject = Subject::factory()->create();
        $quote->subjects()->attach($subject);

        $this->actingAs($this->admin)->delete(route('admin.subjects.destroy', $subject));

        $this->assertCount(0, $quote->fresh()->subjects);
        $this->assertDatabaseHas('quote_subject', ['quote_id' => $quote->id, 'subject_id' => $subject->id]);
    }
}
