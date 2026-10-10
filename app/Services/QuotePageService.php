<?php

namespace App\Services;

use App\Enums\SubjectSourceType;
use App\Enums\SubjectStatus;
use App\Models\Quote;
use App\Models\Subject;
use App\Models\SubjectSource;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Builds the public quote page: the relations it shows and the sharing tags for the
 * server-rendered <head> (there is no SSR, so link-preview bots only see Blade output).
 */
class QuotePageService
{
    public function __construct(private RichTextSanitizer $richTextSanitizer) {}

    public function load(Quote $quote): Quote
    {
        return $quote
            ->load([
                'speaker:id,name',
                'sources' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('id'),
                'tags:id,name,slug',
                'categories:id,name,slug,color',
            ])
            ->makeHidden('user_id');
    }

    /**
     * The quote's subjects the user may see (drafts are admin-only), in the admin's order.
     *
     * The full details are sanitized again on the way out, which also covers content written
     * outside SubjectService (seeders, tinker, a future importer) and any later tightening of
     * the allow-list. Only these fields are sent: never the raw full_details or the pivot.
     *
     * @return list<array{id: int, name: string, slug: string, status: SubjectStatus, full_details_html: ?string, sources: list<array{url: string, title: ?string, source_type: ?SubjectSourceType, archived_url: ?string}>}>
     */
    public function subjects(Quote $quote, ?User $user): array
    {
        return $quote->subjects()
            ->visibleTo($user)
            ->with('sources:id,subject_id,url,title,source_type,archived_url,position')
            ->get(['subjects.id', 'subjects.name', 'subjects.slug', 'subjects.status', 'subjects.full_details'])
            ->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'slug' => $subject->slug,
                'status' => $subject->status,
                'full_details_html' => $this->richTextSanitizer->sanitize($subject->full_details),
                'sources' => $subject->sources
                    ->map(fn (SubjectSource $source) => $source->only(['url', 'title', 'source_type', 'archived_url']))
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array{title: string, description: string, url: string}
     */
    public function meta(Quote $quote): array
    {
        $title = '“'.Str::limit($quote->text, 60, '…', preserveWords: true).'”';

        if ($quote->speaker) {
            $title .= ' — '.$quote->speaker->name;
        }

        $description = $quote->context
            ?: Str::limit(Str::squish((string) $quote->reality_check), 160, '…', preserveWords: true)
            ?: config('app.name');

        return [
            'title' => $title,
            'description' => $description,
            'url' => route('quotes.show', [$quote, $quote->slug]),
        ];
    }
}
