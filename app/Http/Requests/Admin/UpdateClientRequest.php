<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Admin "Edit Client" form (reference: cats-frontend/src/components/ClientForm.tsx).
 *
 * Client-specific fields only — the child/parent/funding fields shown
 * alongside them are read straight from `original_intake` and are not
 * editable here.
 */
class UpdateClientRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    public const STATUSES = ['active', 'paused', 'completed', 'inactive', 'archive'];

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
            'status' => ['required', Rule::in(self::STATUSES)],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'signed_date' => ['nullable', 'date'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string'],
        ];
    }
}
