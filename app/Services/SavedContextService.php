<?php

namespace App\Services;

use App\Models\SavedContext;
use App\Models\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SavedContextService
{
    /**
     * @param  array<string, mixed>  $data  Validated SavedContextRequest data
     */
    public function create(array $data): SavedContext
    {
        return DB::transaction(function () use ($data): SavedContext {
            $savedContext = SavedContext::create(Arr::only($data, ['subject', 'body']));
            $savedContext->tags()->sync(Tag::resolveIds($data['tags'] ?? null));

            return $savedContext;
        });
    }

    /**
     * @param  array<string, mixed>  $data  Validated SavedContextRequest data
     */
    public function update(SavedContext $savedContext, array $data): SavedContext
    {
        return DB::transaction(function () use ($savedContext, $data): SavedContext {
            $savedContext->update(Arr::only($data, ['subject', 'body']));
            $savedContext->tags()->sync(Tag::resolveIds($data['tags'] ?? null));

            return $savedContext;
        });
    }
}
