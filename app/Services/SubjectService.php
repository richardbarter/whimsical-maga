<?php

namespace App\Services;

use App\Models\Subject;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SubjectService
{
    public function __construct(private RichTextSanitizer $richTextSanitizer) {}

    /**
     * @param  array<string, mixed>  $data  Validated SubjectRequest data
     */
    public function create(array $data): Subject
    {
        return DB::transaction(function () use ($data): Subject {
            $subject = Subject::create($this->attributes($data));
            $this->syncSources($subject, $data);

            return $subject;
        });
    }

    /**
     * The slug follows the name, so it's regenerated only when the name changes.
     *
     * @param  array<string, mixed>  $data  Validated SubjectRequest data
     */
    public function update(Subject $subject, array $data): Subject
    {
        return DB::transaction(function () use ($subject, $data): Subject {
            $subject->update([
                ...$this->attributes($data),
                'slug' => $data['name'] === $subject->name
                    ? $subject->slug
                    : Subject::generateUniqueSlug($data['name'], $subject->id),
            ]);
            $this->syncSources($subject, $data);

            return $subject;
        });
    }

    /**
     * Sources have no natural key to sync on, so they're replaced wholesale, taking their
     * position from the submitted order. Like full details, they're left alone when the request
     * doesn't include them. Runs inside the caller's transaction, so a failed insert can't leave
     * the subject with its sources deleted.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncSources(Subject $subject, array $data): void
    {
        if (! array_key_exists('sources', $data)) {
            return;
        }

        $subject->sources()->delete();
        $subject->sources()->createMany(
            collect($data['sources'] ?? [])
                ->values()
                ->map(fn (array $source, int $position) => [
                    ...Arr::only($source, ['url', 'title', 'source_type', 'archived_url']),
                    'position' => $position,
                ])
                ->all()
        );
    }

    /**
     * Full details are sanitized before saving. They're left untouched when the request
     * doesn't include them, so a partial update can't wipe them.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $attributes = Arr::only($data, ['name', 'status']);

        if (array_key_exists('full_details', $data)) {
            $attributes['full_details'] = $this->richTextSanitizer->sanitize($data['full_details']);
        }

        return $attributes;
    }
}
