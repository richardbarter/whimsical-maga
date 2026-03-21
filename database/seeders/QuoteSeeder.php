<?php

namespace Database\Seeders;

use App\Models\Quote;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        Quote::factory(20)
            ->published()
            ->sequence(fn () => [
                'context' => fake()->sentence(),
                'occurred_at' => fake()->dateTimeBetween('-4 years', 'now'),
            ])
            ->create()
            ->each(function (Quote $quote) {
                $tags = Tag::inRandomOrder()->limit(rand(1, 3))->get();

                if ($tags->isEmpty()) {
                    $tags = Tag::factory(rand(1, 3))->create();
                }

                $quote->tags()->attach($tags->pluck('id'));
            });
    }
}
