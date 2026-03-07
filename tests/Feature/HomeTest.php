<?php

namespace Tests\Feature;

use App\Models\Background;
use App\Models\Quote;
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
}
