<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The invoices list's "Create Invoice" modal.
 *
 * An admin invoices one family at a time, so they name the client. A
 * therapist invoices the clinic for everything they billed in the period,
 * whoever it was for, so they give dates alone.
 */
class GenerateInvoiceFromBillingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true || $this->user()?->isTherapist() === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                Rule::requiredIf(fn (): bool => $this->user()?->isAdmin() === true),
                'nullable',
                'integer',
                'exists:clients,id',
            ],
            'date_start' => ['required', 'date'],
            'date_end' => ['required', 'date', 'after_or_equal:date_start'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date_start' => 'start date',
            'date_end' => 'end date',
        ];
    }
}
