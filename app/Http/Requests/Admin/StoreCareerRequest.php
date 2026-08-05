<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin "Add/Edit Position" form, backing the `careers` table that the
 * public Careers listing (App\Http\Controllers\Public\CareerController)
 * reads from.
 */
class StoreCareerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'position' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'schedule' => ['required', 'string', 'max:255'],
            'contract' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'string', 'max:255'],
            'short_description' => ['required', 'string'],
            'about_description' => ['required', 'string'],

            'responsibilities' => ['nullable', 'array'],
            'responsibilities.*' => ['string'],
            'qualifications' => ['nullable', 'array'],
            'qualifications.*' => ['string'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string'],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['string'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['string'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['string'],

            'is_active' => ['boolean'],
            'due_date' => ['nullable', 'date'],
            'level' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:255'],
        ];
    }
}
