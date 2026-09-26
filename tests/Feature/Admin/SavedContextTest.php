<?php

namespace Tests\Feature\Admin;

use App\Models\SavedContext;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedContextTest extends TestCase
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
            'subject' => 'Iran Nuclear Deal History',
            'body' => 'The Iran nuclear deal, formally known as the Joint Comprehensive Plan of Action (JCPOA), was signed in 2015.',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_is_redirected_to_login_for_all_saved_context_routes(): void
    {
        $savedContext = SavedContext::factory()->create();

        $this->get(route('admin.saved-contexts.index'))->assertRedirect(route('login'));
        $this->get(route('admin.saved-contexts.create'))->assertRedirect(route('login'));
        $this->post(route('admin.saved-contexts.store'))->assertRedirect(route('login'));
        $this->get(route('admin.saved-contexts.edit', $savedContext))->assertRedirect(route('login'));
        $this->put(route('admin.saved-contexts.update', $savedContext))->assertRedirect(route('login'));
        $this->delete(route('admin.saved-contexts.destroy', $savedContext))->assertRedirect(route('login'));
        $this->get(route('admin.saved-contexts.search'))->assertRedirect(route('login'));
    }

    public function test_non_admin_user_receives_403_for_all_saved_context_routes(): void
    {
        $savedContext = SavedContext::factory()->create();

        $this->actingAs($this->regularUser)->get(route('admin.saved-contexts.index'))->assertForbidden();
        $this->actingAs($this->regularUser)->get(route('admin.saved-contexts.create'))->assertForbidden();
        $this->actingAs($this->regularUser)->post(route('admin.saved-contexts.store'))->assertForbidden();
        $this->actingAs($this->regularUser)->get(route('admin.saved-contexts.edit', $savedContext))->assertForbidden();
        $this->actingAs($this->regularUser)->put(route('admin.saved-contexts.update', $savedContext))->assertForbidden();
        $this->actingAs($this->regularUser)->delete(route('admin.saved-contexts.destroy', $savedContext))->assertForbidden();
        $this->actingAs($this->regularUser)->get(route('admin.saved-contexts.search'))->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_admin_can_view_saved_contexts_index(): void
    {
        SavedContext::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.saved-contexts.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SavedContexts/Index')
            ->has('savedContexts.data', 3)
        );
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_admin_can_view_create_saved_context_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.saved-contexts.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SavedContexts/Create')
            ->has('tags')
        );
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function test_admin_can_create_a_saved_context(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.saved-contexts.store'), $this->validPayload());

        $response->assertRedirect(route('admin.saved-contexts.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('saved_contexts', 1);
        $this->assertDatabaseHas('saved_contexts', [
            'subject' => 'Iran Nuclear Deal History',
        ]);
    }

    public function test_store_creates_new_tag_when_name_not_found(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.saved-contexts.store'), $this->validPayload([
                'tags' => [['id' => null, 'name' => 'Brand New Tag']],
            ]));

        $this->assertDatabaseHas('tags', ['name' => 'Brand New Tag']);
    }

    public function test_store_reuses_existing_tag_by_id(): void
    {
        $tag = Tag::factory()->create(['name' => 'Existing Tag']);

        $this->actingAs($this->admin)
            ->post(route('admin.saved-contexts.store'), $this->validPayload([
                'tags' => [['id' => $tag->id, 'name' => 'Existing Tag']],
            ]));

        $this->assertDatabaseCount('tags', 1);
    }

    public function test_store_syncs_tags_to_pivot(): void
    {
        $tag = Tag::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.saved-contexts.store'), $this->validPayload([
                'tags' => [['id' => $tag->id, 'name' => $tag->name]],
            ]));

        $savedContext = SavedContext::first();
        $this->assertDatabaseHas('saved_context_tag', [
            'saved_context_id' => $savedContext->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_store_validation_requires_subject(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.saved-contexts.store'), $this->validPayload(['subject' => '']));

        $response->assertSessionHasErrors(['subject']);
    }

    public function test_store_validation_requires_body(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.saved-contexts.store'), $this->validPayload(['body' => '']));

        $response->assertSessionHasErrors(['body']);
    }

    public function test_store_validation_fails_when_body_exceeds_2000_chars(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.saved-contexts.store'), $this->validPayload([
                'body' => str_repeat('a', 2001),
            ]));

        $response->assertSessionHasErrors(['body']);
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function test_admin_can_view_edit_saved_context_form(): void
    {
        $savedContext = SavedContext::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.saved-contexts.edit', $savedContext));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SavedContexts/Edit')
            ->has('savedContext')
            ->where('savedContext.id', $savedContext->id)
            ->has('tags')
        );
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function test_admin_can_update_a_saved_context(): void
    {
        $savedContext = SavedContext::factory()->create(['subject' => 'Old Subject']);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.saved-contexts.update', $savedContext), $this->validPayload([
                'subject' => 'Updated Subject',
            ]));

        $response->assertRedirect(route('admin.saved-contexts.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('saved_contexts', [
            'id' => $savedContext->id,
            'subject' => 'Updated Subject',
        ]);
    }

    public function test_update_syncs_tags(): void
    {
        $savedContext = SavedContext::factory()->create();
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $savedContext->tags()->attach($oldTag);

        $this->actingAs($this->admin)
            ->put(route('admin.saved-contexts.update', $savedContext), $this->validPayload([
                'tags' => [['id' => $newTag->id, 'name' => $newTag->name]],
            ]));

        $this->assertDatabaseHas('saved_context_tag', [
            'saved_context_id' => $savedContext->id,
            'tag_id' => $newTag->id,
        ]);
        $this->assertDatabaseMissing('saved_context_tag', [
            'saved_context_id' => $savedContext->id,
            'tag_id' => $oldTag->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function test_admin_can_delete_a_saved_context(): void
    {
        $savedContext = SavedContext::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.saved-contexts.destroy', $savedContext));

        $response->assertRedirect(route('admin.saved-contexts.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('saved_contexts', 0);
    }

    public function test_destroy_detaches_tags_from_pivot(): void
    {
        $savedContext = SavedContext::factory()->create();
        $tag = Tag::factory()->create();
        $savedContext->tags()->attach($tag);

        $this->actingAs($this->admin)
            ->delete(route('admin.saved-contexts.destroy', $savedContext));

        $this->assertDatabaseMissing('saved_context_tag', [
            'saved_context_id' => $savedContext->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Search
    // -------------------------------------------------------------------------

    public function test_search_returns_json_for_admin(): void
    {
        SavedContext::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.saved-contexts.search'));

        $response->assertOk();
        $response->assertJsonCount(1);
    }

    public function test_search_filters_by_subject_query(): void
    {
        SavedContext::factory()->create(['subject' => 'Iran Nuclear Deal']);
        SavedContext::factory()->create(['subject' => 'January 6th Overview']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.saved-contexts.search', ['q' => 'Iran']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.subject', 'Iran Nuclear Deal');
    }

    public function test_search_is_case_insensitive(): void
    {
        SavedContext::factory()->create(['subject' => 'Iran Nuclear Deal']);
        SavedContext::factory()->create(['subject' => 'January 6th Overview']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.saved-contexts.search', ['q' => 'iran']));

        $response->assertOk();
        $response->assertJsonCount(1);
    }

    public function test_search_filters_by_tag_name(): void
    {
        $iranTag = Tag::factory()->create(['name' => 'Iran']);
        $jan6Tag = Tag::factory()->create(['name' => 'January 6th']);

        $iranContext = SavedContext::factory()->create(['subject' => 'Nuclear Deal Overview']);
        $jan6Context = SavedContext::factory()->create(['subject' => 'Capitol Events']);

        $iranContext->tags()->attach($iranTag);
        $jan6Context->tags()->attach($jan6Tag);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.saved-contexts.search', ['q' => 'Iran']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.subject', 'Nuclear Deal Overview');
    }

    public function test_search_with_comma_separated_terms_uses_and_logic(): void
    {
        $iranTag = Tag::factory()->create(['name' => 'Iran']);

        $bothContext = SavedContext::factory()->create(['subject' => 'Trump Iran Policy']);
        $iranOnlyContext = SavedContext::factory()->create(['subject' => 'Iran Nuclear Deal']);
        $trumpOnlyContext = SavedContext::factory()->create(['subject' => 'Trump Economy']);

        $bothContext->tags()->attach($iranTag);
        $iranOnlyContext->tags()->attach($iranTag);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.saved-contexts.search', ['q' => 'Trump, Iran']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.subject', 'Trump Iran Policy');
    }

    public function test_search_returns_empty_array_when_no_matches(): void
    {
        SavedContext::factory()->create(['subject' => 'Iran Nuclear Deal']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.saved-contexts.search', ['q' => 'something-that-doesnt-exist']));

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    public function test_store_returns_the_created_context_as_json_for_the_quote_form_dialog(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.saved-contexts.store'), $this->validPayload([
            'tags' => [['id' => null, 'name' => 'Brand New Tag']],
        ]));

        $response->assertCreated()
            ->assertJsonPath('subject', 'Iran Nuclear Deal History')
            ->assertJsonPath('tags.0.name', 'Brand New Tag');
    }

    public function test_store_json_validation_errors_are_returned_as_json(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.saved-contexts.store'), ['subject' => '', 'body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subject', 'body']);
    }

    public function test_search_rejects_a_non_string_query_instead_of_erroring(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.saved-contexts.search', ['q' => ['not', 'a', 'string']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_search_ignores_blank_terms_between_commas(): void
    {
        SavedContext::factory()->create(['subject' => 'Iran Nuclear Deal']);

        $this->actingAs($this->admin)
            ->getJson(route('admin.saved-contexts.search', ['q' => ' , Iran ,, ']))
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_store_validation_fails_with_a_nonexistent_tag_id(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.saved-contexts.store'), $this->validPayload([
                'tags' => [['id' => 99999, 'name' => 'Deleted tag']],
            ]))
            ->assertSessionHasErrors('tags.0.id');

        $this->assertDatabaseCount('saved_contexts', 0);
    }

    public function test_store_reuses_an_existing_tag_when_a_new_name_differs_only_by_case(): void
    {
        $existing = Tag::factory()->create(['name' => 'January 6th']);

        $this->actingAs($this->admin)->post(route('admin.saved-contexts.store'), $this->validPayload([
            'tags' => [['id' => null, 'name' => 'january 6th']],
        ]));

        $this->assertSame(1, Tag::count());
        $this->assertEquals([$existing->id], SavedContext::first()->tags->pluck('id')->all());
    }
}
