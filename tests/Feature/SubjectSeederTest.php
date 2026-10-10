<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Enums\SubjectStatus;
use App\Models\Quote;
use App\Models\Subject;
use App\Models\SubjectSource;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_published_and_draft_subjects(): void
    {
        $this->seed(SubjectSeeder::class);

        $this->assertSame(6, Subject::published()->count());
        $this->assertSame(4, Subject::where('status', SubjectStatus::Draft)->count());
        $this->assertSame(2, Subject::whereNull('full_details')->count());
        $this->assertStringContainsString('<table>', Subject::published()->first()->full_details);
    }

    public function test_subjects_with_full_details_get_ordered_sources(): void
    {
        $this->seed(SubjectSeeder::class);

        foreach (Subject::whereNotNull('full_details')->with('sources')->get() as $subject) {
            $this->assertGreaterThanOrEqual(1, $subject->sources->count());
            $this->assertEquals(range(0, $subject->sources->count() - 1), $subject->sources->pluck('position')->all());
        }

        $this->assertSame(0, SubjectSource::whereIn('subject_id', Subject::whereNull('full_details')->select('id'))->count());
    }

    public function test_seeder_works_without_any_quotes(): void
    {
        $this->seed(SubjectSeeder::class);

        $this->assertSame(10, Subject::count());
        $this->assertDatabaseCount('quote_subject', 0);
    }

    public function test_seeder_links_two_in_three_published_quotes_with_ordered_positions(): void
    {
        Quote::factory(9)->published()->create();

        $this->seed(SubjectSeeder::class);

        $linkedQuotes = Quote::has('subjects')->with('subjects')->get();
        $this->assertCount(6, $linkedQuotes);

        foreach ($linkedQuotes as $quote) {
            $this->assertEquals(
                range(0, $quote->subjects->count() - 1),
                $quote->subjects->pluck('pivot.position')->all()
            );
        }
    }

    public function test_seeder_leaves_unpublished_quotes_and_existing_links_alone(): void
    {
        $draftQuote = Quote::factory()->create(['status' => QuoteStatus::Draft]);
        $linkedQuote = Quote::factory()->published()->create();
        $existingSubject = Subject::factory()->create();
        $linkedQuote->subjects()->attach($existingSubject);

        $this->seed(SubjectSeeder::class);

        $this->assertCount(0, $draftQuote->subjects);
        $this->assertEquals([$existingSubject->id], $linkedQuote->subjects()->pluck('subjects.id')->all());
    }
}
