<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubjectImageRequest extends FormRequest
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
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'alt_text' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'credit' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'alt_text' => 'alt text',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alt_text.required' => 'Describe the image for people who can\'t see it.',
            'image.max' => 'The image must be 10 MB or smaller.',
            'image.mimes' => 'The image must be a JPG, PNG or WebP file.',
        ];
    }
}
