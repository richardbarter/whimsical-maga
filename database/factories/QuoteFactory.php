<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $text = fake()->paragraph();

        return [
            'text' => $text,
            'speaker_id' => Speaker::factory(),
            'slug' => Quote::generateSlug($text),
            'status' => 'draft',
            'quote_type' => 'spoken',
            'is_verified' => false,
            'is_featured' => false,
            'context' => null,
            'location' => null,
            'occurred_at' => null,
            'published_at' => null,
            'user_id' => User::factory()->admin(),
        ];
    }

    /**
     * Set status to published and record publication timestamp.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Mark the quote as featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Mark the quote as verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }
}
