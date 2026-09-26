<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SavedContextSearchRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The comma-separated search terms, trimmed, with blanks removed.
     *
     * @return array<int, string>
     */
    public function terms(): array
    {
        $terms = array_map('trim', explode(',', (string) $this->validated('q')));

        return array_values(array_filter($terms, fn (string $term) => $term !== ''));
    }
}
