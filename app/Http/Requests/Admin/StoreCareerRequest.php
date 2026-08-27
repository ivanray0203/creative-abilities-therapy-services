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
        return array_merge([
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
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'due_date' => ['nullable', 'date'],
            'level' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:255'],
        ], $this->detailRules());
    }

    /**
     * The posting narrative rendered by `public/career-detail`. Every part is
     * optional so a posting can be published with only the basics filled in.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    private function detailRules(): array
    {
        return array_merge([
            'detail' => ['nullable', 'array'],
            'detail.intro' => ['nullable', 'array'],
            'detail.intro.*' => ['string'],
            'detail.role_summary' => ['nullable', 'string'],
            'detail.responsibilities_lead_in' => ['nullable', 'string'],
            'detail.qualifications_lead_in' => ['nullable', 'string'],
            'detail.qualifications_note' => ['nullable', 'string'],
            'detail.closing_title' => ['nullable', 'string', 'max:255'],
            'detail.closing' => ['nullable', 'string'],

            'detail.offers' => ['nullable', 'array'],
            'detail.offers.*.title' => ['required_with:detail.offers', 'string', 'max:255'],
            'detail.offers.*.description' => ['required_with:detail.offers', 'string'],

            'detail.extras' => ['nullable', 'array'],
            'detail.extras.*.title' => ['required_with:detail.extras', 'string', 'max:255'],
            'detail.extras.*.paragraphs' => ['nullable', 'array'],
            'detail.extras.*.paragraphs.*' => ['string'],
            'detail.extras.*.lead_in' => ['nullable', 'string'],
            'detail.extras.*.items' => ['nullable', 'array'],
            'detail.extras.*.items.*' => ['string'],
            'detail.extras.*.closing' => ['nullable', 'string'],

            'detail.contractor' => ['nullable', 'array'],
            'detail.contractor.title' => ['nullable', 'string', 'max:255'],
            'detail.contractor.paragraphs' => ['nullable', 'array'],
            'detail.contractor.paragraphs.*' => ['string'],
        ], $this->sectionRules('detail.collaboration'), $this->sectionRules('detail.fscd'));
    }

    /**
     * Prose wrapped around a bullet list — the shape both the collaboration
     * and FSCD blocks take.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    private function sectionRules(string $key): array
    {
        return [
            $key => ['nullable', 'array'],
            "{$key}.title" => ['nullable', 'string', 'max:255'],
            "{$key}.intro" => ['nullable', 'string'],
            "{$key}.lead_in" => ['nullable', 'string'],
            "{$key}.items" => ['nullable', 'array'],
            "{$key}.items.*" => ['string'],
            "{$key}.closing" => ['nullable', 'string'],
        ];
    }
}
