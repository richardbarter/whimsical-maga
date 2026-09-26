<?php

namespace Database\Factories;

use App\Models\SavedContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedContext>
 */
class SavedContextFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(3),
        ];
    }
}
