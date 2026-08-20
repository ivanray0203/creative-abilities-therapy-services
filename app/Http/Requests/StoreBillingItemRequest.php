<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The "Create Bill" form: one client, an optional session, and a service
 * line for every service delivered — each line is saved as its own
 * billing item.
 *
 * `therapist_id`/`issued_by_id` are not accepted here; the controller
 * forces both from the acting user, same pattern as StoreInvoiceRequest.
 */
class StoreBillingItemRequest extends FormRequest
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
            // An admin raises a bill on a therapist's behalf, so they say
            // whose it is. A therapist's own bill is always their own.
            'therapist_id' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->user()?->isAdmin() === true),
                'integer',
                Rule::exists('users', 'id')->where('role', 'therapist'),
            ],
            'session_id' => ['nullable', 'integer', 'exists:schedule_sessions,id'],
            'notes' => ['nullable', 'string'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.invoice_service_id' => ['nullable', 'integer', 'exists:invoice_services,id'],
            'services.*.name' => ['required', 'string', 'max:255'],
            // Billable hours, so fractional: 0.75 and 1.5 are real quantities.
            'services.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'services.*.rate' => ['required', 'numeric', 'min:0'],
        ];
    }
}
