<?php

namespace App\Http\Requests\Admin;

use App\Enums\QuoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:5000'],
            'speaker' => ['required', 'string', 'max:255'],
            'context' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date'],
            'is_verified' => ['boolean'],
            'is_featured' => ['boolean'],
            'status' => ['required', 'in:published,draft,pending'],
            'quote_type' => ['required', 'string', Rule::enum(QuoteType::class)],
            'quote_type_note' => ['nullable', 'string', 'max:255'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['required', 'string'],

            'categories' => ['nullable', 'array'],
            'categories.*' => ['required', 'string'],

            'sources' => ['nullable', 'array'],
            'sources.*.url' => ['required', 'url', 'max:2048'],
            'sources.*.title' => ['nullable', 'string', 'max:255'],
            'sources.*.source_type' => ['nullable', 'string', 'in:tweet,article,video,speech,interview,press_conference,rally,social_media,book,other'],
            'sources.*.is_primary' => ['boolean'],
            'sources.*.archived_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
