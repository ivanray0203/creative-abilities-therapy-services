<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared admin/therapist "Add/Edit Invoice" form (reference:
 * cats-frontend/src/forms/InvoiceForm.tsx).
 *
 * `therapist_id`/`billed_by` are not accepted here — the controller forces
 * both from the acting user, same pattern as StoreSessionRequest.
 */
class StoreInvoiceRequest extends FormRequest
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
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'session_id' => ['nullable', 'integer', 'exists:schedule_sessions,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'action' => ['required', Rule::in(['draft', 'send'])],
            'services' => ['required', 'array', 'min:1'],
            'services.*.name' => ['required', 'string', 'max:255'],
            'services.*.description' => ['nullable', 'string', 'max:255'],
            'services.*.numberOfSessions' => ['required', 'integer', 'min:1'],
            'services.*.rate_numeric' => ['required', 'numeric', 'min:0'],
        ];
    }
}
