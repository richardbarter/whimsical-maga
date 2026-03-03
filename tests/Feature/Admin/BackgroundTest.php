<?php

namespace Tests\Feature\Admin;

use App\Models\Background;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackgroundTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
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
            'image' => UploadedFile::fake()->image('background.jpg', 1920, 1080),
            'title' => 'Test Background',
            'alt_text' => 'A beautiful scenic background',
            'credit' => 'Test Photographer',
            'source_url' => 'https://unsplash.com/photos/abc123',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_is_redirected_to_login_for_all_background_routes(): void
    {
        $background = Background::factory()->create();

        $this->get(route('admin.backgrounds.index'))->assertRedirect(route('login'));
        $this->get(route('admin.backgrounds.create'))->assertRedirect(route('login'));
        $this->post(route('admin.backgrounds.store'))->assertRedirect(route('login'));
        $this->get(route('admin.backgrounds.edit', $background))->assertRedirect(route('login'));
        $this->put(route('admin.backgrounds.update', $background))->assertRedirect(route('login'));
        $this->delete(route('admin.backgrounds.destroy', $background))->assertRedirect(route('login'));
    }

    public function test_non_admin_user_receives_403_for_all_background_routes(): void
    {
        $background = Background::factory()->create();

        $this->actingAs($this->regularUser)->get(route('admin.backgrounds.index'))->assertForbidden();
        $this->actingAs($this->regularUser)->get(route('admin.backgrounds.create'))->assertForbidden();
        $this->actingAs($this->regularUser)->post(route('admin.backgrounds.store'))->assertForbidden();
        $this->actingAs($this->regularUser)->get(route('admin.backgrounds.edit', $background))->assertForbidden();
        $this->actingAs($this->regularUser)->put(route('admin.backgrounds.update', $background))->assertForbidden();
        $this->actingAs($this->regularUser)->delete(route('admin.backgrounds.destroy', $background))->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_admin_can_view_backgrounds_index(): void
    {
        Background::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.backgrounds.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Backgrounds/Index')
            ->has('backgrounds.data', 3)
        );
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_admin_can_view_create_background_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.backgrounds.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Backgrounds/Create')
        );
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function test_admin_can_create_a_background(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.backgrounds.store'), $this->validPayload());

        $response->assertRedirect(route('admin.backgrounds.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('backgrounds', 1);
    }

    public function test_store_saves_file_to_storage(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.backgrounds.store'), $this->validPayload());

        $background = Background::first();
        Storage::disk('public')->assertExists($background->file_path);
    }

    public function test_store_extracts_file_size_and_dimensions(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.backgrounds.store'), $this->validPayload());

        $background = Background::first();
        $this->assertNotNull($background->file_size);
        $this->assertNotNull($background->dimensions);
    }

    public function test_store_saves_source_url(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.backgrounds.store'), $this->validPayload([
                'source_url' => 'https://unsplash.com/photos/xyz',
            ]));

        $this->assertDatabaseHas('backgrounds', ['source_url' => 'https://unsplash.com/photos/xyz']);
    }

    public function test_store_validation_fails_with_invalid_source_url(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.backgrounds.store'), $this->validPayload([
                'source_url' => 'not-a-valid-url',
            ]));

        $response->assertSessionHasErrors(['source_url']);
    }

    public function test_store_validation_fails_when_image_is_missing(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.backgrounds.store'), ['title' => 'No Image']);

        $response->assertSessionHasErrors(['image']);
    }

    public function test_store_validation_fails_with_invalid_mime_type(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.backgrounds.store'), [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            'title' => 'Bad Mime',
        ]);

        $response->assertSessionHasErrors(['image']);
    }

    public function test_store_validation_fails_with_oversized_file(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.backgrounds.store'), [
            'image' => UploadedFile::fake()->image('big.jpg')->size(11000),
        ]);

        $response->assertSessionHasErrors(['image']);
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function test_admin_can_view_edit_form_with_background_data(): void
    {
        $background = Background::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.backgrounds.edit', $background));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Backgrounds/Edit')
            ->has('background')
            ->where('background.id', $background->id)
        );
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function test_admin_can_update_background_metadata_without_new_image(): void
    {
        $background = Background::factory()->create(['title' => 'Old Title']);
        $originalFilePath = $background->file_path;

        $response = $this->actingAs($this->admin)->put(
            route('admin.backgrounds.update', $background),
            ['title' => 'Updated Title', 'alt_text' => 'New alt text']
        );

        $response->assertRedirect(route('admin.backgrounds.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('backgrounds', [
            'id' => $background->id,
            'title' => 'Updated Title',
            'file_path' => $originalFilePath,
        ]);
    }

    public function test_admin_can_replace_background_image(): void
    {
        $background = Background::factory()->create();

        $this->actingAs($this->admin)->put(
            route('admin.backgrounds.update', $background),
            ['image' => UploadedFile::fake()->image('new-background.png', 1920, 1080)]
        );

        $updatedBackground = $background->fresh();
        $this->assertNotEquals($background->file_path, $updatedBackground->file_path);
        Storage::disk('public')->assertExists($updatedBackground->file_path);
    }

    public function test_update_deletes_old_file_when_replacing_image(): void
    {
        // Place a fake file in storage to simulate an existing background image.
        $oldPath = 'backgrounds/old-image.jpg';
        Storage::disk('public')->put($oldPath, 'fake image content');
        $background = Background::factory()->create(['file_path' => $oldPath]);

        $this->actingAs($this->admin)->put(
            route('admin.backgrounds.update', $background),
            ['image' => UploadedFile::fake()->image('replacement.jpg', 1920, 1080)]
        );

        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_update_validation_fails_with_invalid_mime_on_replacement(): void
    {
        $background = Background::factory()->create();

        $response = $this->actingAs($this->admin)->put(
            route('admin.backgrounds.update', $background),
            ['image' => UploadedFile::fake()->create('script.exe', 100, 'application/octet-stream')]
        );

        $response->assertSessionHasErrors(['image']);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function test_admin_can_delete_a_background(): void
    {
        $background = Background::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.backgrounds.destroy', $background));

        $response->assertRedirect(route('admin.backgrounds.index'));
        $response->assertSessionHas('success');
    }

    public function test_destroy_soft_deletes_the_background(): void
    {
        $background = Background::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.backgrounds.destroy', $background));

        $this->assertSoftDeleted('backgrounds', ['id' => $background->id]);
    }

    public function test_destroy_preserves_file_in_storage_on_soft_delete(): void
    {
        $filePath = 'backgrounds/to-delete.jpg';
        Storage::disk('public')->put($filePath, 'fake image content');
        $background = Background::factory()->create(['file_path' => $filePath]);

        $this->actingAs($this->admin)->delete(route('admin.backgrounds.destroy', $background));

        Storage::disk('public')->assertExists($filePath);
    }

    public function test_force_delete_removes_file_from_storage(): void
    {
        $filePath = 'backgrounds/to-force-delete.jpg';
        Storage::disk('public')->put($filePath, 'fake image content');
        $background = Background::factory()->create(['file_path' => $filePath]);

        $background->forceDelete();

        Storage::disk('public')->assertMissing($filePath);
    }
}
