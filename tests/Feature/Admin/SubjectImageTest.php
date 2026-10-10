<?php

namespace Tests\Feature\Admin;

use App\Models\Subject;
use App\Models\SubjectImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\CreatesImagesWithMetadata;
use Tests\TestCase;

class SubjectImageTest extends TestCase
{
    use CreatesImagesWithMetadata, RefreshDatabase;

    private User $admin;

    private User $regularUser;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->admin = User::factory()->admin()->create();
        $this->regularUser = User::factory()->asUser()->create();
        $this->subject = Subject::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'image' => UploadedFile::fake()->image('chart.png', 1200, 800),
            'alt_text' => 'Bar chart of fraud rates by year',
            'caption' => 'Fraud rates stayed below 0.001%',
            'credit' => 'Brennan Center',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_guests_are_redirected_and_non_admins_forbidden(): void
    {
        $image = SubjectImage::factory()->for($this->subject)->create();

        $this->post(route('admin.subjects.images.store', $this->subject))->assertRedirect(route('login'));
        $this->delete(route('admin.subjects.images.destroy', [$this->subject, $image]))->assertRedirect(route('login'));

        $this->actingAs($this->regularUser)
            ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload())
            ->assertForbidden();
        $this->actingAs($this->regularUser)
            ->deleteJson(route('admin.subjects.images.destroy', [$this->subject, $image]))
            ->assertForbidden();

        $this->assertSame(1, SubjectImage::count());
        $this->assertSame([], Storage::files(SubjectImage::FOLDER));
    }

    // -------------------------------------------------------------------------
    // Upload
    // -------------------------------------------------------------------------

    public function test_admin_can_upload_an_image_and_gets_its_url_and_size(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload())
            ->assertCreated();

        $image = SubjectImage::sole();
        $response->assertExactJson([
            'id' => $image->id,
            'url' => Storage::url($image->file_path),
            'width' => 1200,
            'height' => 800,
        ]);

        Storage::assertExists($image->file_path);
        $this->assertStringStartsWith(SubjectImage::FOLDER.'/', $image->file_path);
        $this->assertSame($this->subject->id, $image->subject_id);
        $this->assertSame('Bar chart of fraud rates by year', $image->alt_text);
        $this->assertSame('Fraud rates stayed below 0.001%', $image->caption);
        $this->assertSame('Brennan Center', $image->credit);
        $this->assertSame('1200x800', $image->dimensions);
        $this->assertSame(strlen(Storage::get($image->file_path)), $image->file_size);
    }

    public function test_caption_and_credit_are_optional(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload(['caption' => null, 'credit' => null]))
            ->assertCreated();

        $this->assertNull(SubjectImage::sole()->caption);
    }

    public function test_upload_strips_location_metadata(): void
    {
        $upload = UploadedFile::fake()->createWithContent('phone-photo.jpg', $this->jpegWithMetadata('LAT-38.8977-LNG-77.0365'));

        $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload(['image' => $upload]))
            ->assertCreated();

        $storedBytes = Storage::get(SubjectImage::sole()->file_path);
        $this->assertStringNotContainsString('LAT-38.8977-LNG-77.0365', $storedBytes);
        $this->assertNotFalse(imagecreatefromstring($storedBytes));
    }

    public function test_upload_to_a_deleted_subject_is_not_found(): void
    {
        $this->subject->delete();

        $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload())
            ->assertNotFound();

        $this->assertSame([], Storage::files(SubjectImage::FOLDER));
    }

    public function test_a_failed_insert_deletes_the_stored_file(): void
    {
        SubjectImage::creating(fn () => throw new RuntimeException('Simulated database failure'));

        $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload())
            ->assertServerError();

        $this->assertSame([], Storage::files(SubjectImage::FOLDER));
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_alt_text_is_required(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload(['alt_text' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['alt_text' => "Describe the image for people who can't see it."]);

        $this->assertSame(0, SubjectImage::count());
    }

    public function test_image_is_required(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload(['image' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_only_jpg_png_and_webp_images_are_accepted(): void
    {
        foreach ([
            UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->image('animation.gif'),
            UploadedFile::fake()->createWithContent('drawing.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        ] as $file) {
            $this->actingAs($this->admin)
                ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload(['image' => $file]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['image']);
        }

        $this->assertSame([], Storage::files(SubjectImage::FOLDER));
    }

    public function test_images_over_10_mb_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload([
                'image' => UploadedFile::fake()->image('huge.jpg')->size(10241),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image' => 'The image must be 10 MB or smaller.']);
    }

    public function test_text_fields_are_limited_to_255_characters(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.images.store', $this->subject), $this->validPayload([
                'alt_text' => str_repeat('a', 256),
                'caption' => str_repeat('a', 256),
                'credit' => str_repeat('a', 256),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['alt_text', 'caption', 'credit']);
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_and_keeps_the_file(): void
    {
        Storage::put('subject-images/keep.jpg', 'image');
        $image = SubjectImage::factory()->for($this->subject)->create(['file_path' => 'subject-images/keep.jpg']);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.subjects.images.destroy', [$this->subject, $image]))
            ->assertNoContent();

        $this->assertSoftDeleted($image);
        Storage::assertExists('subject-images/keep.jpg');
    }

    public function test_destroy_only_finds_images_of_that_subject(): void
    {
        $otherImage = SubjectImage::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.subjects.images.destroy', [$this->subject, $otherImage]))
            ->assertNotFound();

        $this->assertNotSoftDeleted($otherImage);
    }

    public function test_force_delete_removes_the_file(): void
    {
        Storage::put('subject-images/gone.jpg', 'image');
        $image = SubjectImage::factory()->for($this->subject)->create(['file_path' => 'subject-images/gone.jpg']);

        $image->forceDelete();

        Storage::assertMissing('subject-images/gone.jpg');
    }
}
