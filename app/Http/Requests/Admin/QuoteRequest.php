<?php

namespace App\Http\Requests\Admin;

use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Enums\SourceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:5000'],
            'speaker' => ['required', 'string', 'max:255'],
            'context' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:255'],
            // "tomorrow" (UTC) rather than "today" so admins in time zones ahead of UTC can enter today's date.
            'occurred_at' => ['nullable', 'date', 'before_or_equal:tomorrow'],
            'is_verified' => ['boolean'],
            'is_featured' => ['boolean'],
            'status' => ['required', Rule::enum(QuoteStatus::class)],
            'quote_type' => ['required', 'string', Rule::enum(QuoteType::class)],
            'quote_type_note' => ['nullable', 'string', 'max:255'],
            'claim' => ['nullable', 'string', 'max:10000'],
            'reality_check' => ['nullable', 'string', 'max:10000'],

            'tags' => ['nullable', 'array'],
            'tags.*.id' => ['nullable', 'integer', Rule::exists('tags', 'id')],
            'tags.*.name' => ['required', 'string', 'max:255'],

            'categories' => ['nullable', 'array'],
            'categories.*.id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'categories.*.name' => ['required', 'string', 'max:255'],

            'sources' => ['nullable', 'array'],
            'sources.*.url' => ['required', 'url:http,https', 'max:2048'],
            'sources.*.title' => ['nullable', 'string', 'max:255'],
            'sources.*.source_type' => ['nullable', Rule::enum(SourceType::class)],
            'sources.*.is_primary' => ['boolean'],
            'sources.*.archived_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    /**
     * Human-readable names, so errors read "The source URL…" rather than "The sources.0.url…".
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'occurred_at' => 'date occurred',
            'quote_type' => 'quote type',
            'quote_type_note' => 'quote type description',
            'reality_check' => 'reality check',
            'tags.*.id' => 'tag',
            'tags.*.name' => 'tag name',
            'categories.*.id' => 'category',
            'categories.*.name' => 'category name',
            'sources.*.url' => 'source URL',
            'sources.*.title' => 'source title',
            'sources.*.source_type' => 'source type',
            'sources.*.is_primary' => 'primary source',
            'sources.*.archived_url' => 'archived URL',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'occurred_at.before_or_equal' => 'The date occurred cannot be in the future.',
            'tags.*.id.exists' => 'One of the selected tags no longer exists. Please reload the page.',
            'categories.*.id.exists' => 'One of the selected categories no longer exists. Please reload the page.',
        ];
    }
}
