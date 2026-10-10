<?php

namespace App\Http\Requests\Admin;

use App\Enums\SubjectSourceType;
use App\Enums\SubjectStatus;
use App\Services\RichTextSanitizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(SubjectStatus::class)],
            'full_details' => ['nullable', 'string', 'max:'.RichTextSanitizer::MAX_LENGTH],

            'sources' => ['nullable', 'array'],
            'sources.*.url' => ['required', 'url:http,https', 'max:2048'],
            'sources.*.title' => ['nullable', 'string', 'max:255'],
            'sources.*.source_type' => ['nullable', Rule::enum(SubjectSourceType::class)],
            'sources.*.archived_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    /**
     * Field names as the form labels them ("name" is shown as "Title").
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'title',
            'full_details' => 'full details',
            'sources.*.url' => 'source URL',
            'sources.*.title' => 'source title',
            'sources.*.source_type' => 'source type',
            'sources.*.archived_url' => 'archived URL',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_details.max' => 'The full details are too long to save (including formatting). Try splitting them across two subjects.',
        ];
    }
}
