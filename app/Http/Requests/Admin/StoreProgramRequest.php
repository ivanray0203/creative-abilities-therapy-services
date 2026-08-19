<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin "Add/Edit Program" form, backing the `programs` table the public
 * Programs pages (App\Http\Controllers\Public\ProgramController) read from.
 */
class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * The slug is the public URL, so it is derived from the name rather than
     * typed — one less way for two programmes to collide.
     *
     * Blank highlight rows are dropped here rather than only in the form: an
     * untouched repeater row arrives as null (ConvertEmptyStringsToNull) and
     * would otherwise fail the string rule, or render as an empty bullet.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) $this->input('slug') ?: (string) $this->input('name')),
            'highlights' => $this->collect('highlights')
                ->filter(fn (mixed $highlight): bool => filled($highlight))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('programs', 'slug')->ignore($this->programId()),
            ],
            'category' => ['nullable', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'age_range' => ['nullable', 'string', 'max:255'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'registration_closes_on' => ['nullable', 'date', 'before_or_equal:starts_on'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * The program being edited, so its own slug doesn't collide with itself.
     * Null when storing.
     */
    protected function programId(): ?int
    {
        return null;
    }
}
