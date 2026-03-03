<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Background>
 */
class BackgroundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_path' => 'backgrounds/test-'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(4),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'credit' => fake()->name(),
            'file_size' => fake()->numberBetween(100000, 5000000),
            'dimensions' => '1920x1080',
        ];
    }
}
