<?php

namespace Database\Factories;

use App\Enums\SubjectSourceType;
use App\Models\Subject;
use App\Models\SubjectSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubjectSource>
 */
class SubjectSourceFactory extends Factory
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
            'url' => fake()->url(),
            'title' => fake()->optional(0.8)->sentence(5),
            'source_type' => fake()->randomElement(SubjectSourceType::cases()),
            'archived_url' => fake()->boolean(30) ? 'https://web.archive.org/web/2024/'.fake()->url() : null,
            'position' => 0,
        ];
    }
}
