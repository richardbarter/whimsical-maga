<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\SubjectImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubjectImage>
 */
class SubjectImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'file_path' => SubjectImage::FOLDER.'/test-'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(6),
            'caption' => fake()->optional()->sentence(),
            'credit' => fake()->optional()->name(),
            'file_size' => fake()->numberBetween(50_000, 2_000_000),
            'dimensions' => '1200x800',
        ];
    }
}
