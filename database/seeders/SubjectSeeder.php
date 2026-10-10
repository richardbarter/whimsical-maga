<?php

namespace Database\Seeders;

use App\Models\Quote;
use App\Models\Subject;
use App\Models\SubjectSource;
use Illuminate\Database\Seeder;

/**
 * Development data, run on its own: php artisan db:seed --class=SubjectSeeder
 *
 * Creates published subjects with rich full details and 1–3 sources each, plus a few drafts,
 * then links them to published quotes that have no subjects yet. Every third of those quotes
 * is left without subjects, so pages without full details can be checked too. Existing links
 * are kept.
 */
class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = collect([
            ...Subject::factory(6)->published()->withRichDetails()->create(),
            ...Subject::factory(2)->withRichDetails()->create(),
            ...Subject::factory(2)->withoutDetails()->create(),
        ]);

        $subjects->whereNotNull('full_details')->each(function (Subject $subject): void {
            SubjectSource::factory(rand(1, 3))
                ->for($subject)
                ->sequence(fn ($sequence) => ['position' => $sequence->index])
                ->create();
        });

        Quote::published()
            ->whereDoesntHave('subjects')
            ->inRandomOrder()
            ->get()
            ->each(function (Quote $quote, int $index) use ($subjects): void {
                if ($index % 3 === 2) {
                    return;
                }

                $quote->subjects()->attach(
                    $subjects->random(rand(1, 3))
                        ->values()
                        ->mapWithKeys(fn (Subject $subject, int $position) => [$subject->id => ['position' => $position]])
                        ->all()
                );
            });
    }
}
