<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Closure;
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
            // Admin-only: the therapist's bill this invoice recovers. The
            // controller ignores it on a therapist's own submission.
            'linked_therapist_invoice_id' => ['nullable', 'integer', 'exists:invoices,id', $this->recoverableTherapistInvoice()],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            // No tax_percentage rule: the form no longer offers one, and
            // accepting it would let a crafted request put GST back on.
            'notes' => ['nullable', 'string'],
            'action' => ['required', Rule::in(['draft', 'send'])],
            'services' => ['required', 'array', 'min:1'],
            'services.*.name' => ['required', 'string', 'max:255'],
            'services.*.description' => ['nullable', 'string', 'max:255'],
            'services.*.numberOfSessions' => ['required', 'integer', 'min:1'],
            'services.*.rate_numeric' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * The clinic can only recover a therapist's bill against the child that
     * bill was for — linking to another family's invoice would put the wrong
     * cost behind what the parent is being charged.
     */
    private function recoverableTherapistInvoice(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $therapistInvoice = Invoice::query()->whereKey($value)->first();

            if ($therapistInvoice?->billed_by !== 'therapist') {
                $fail('Only a therapist invoice can be recovered by this invoice.');

                return;
            }

            if ($therapistInvoice->client_id !== $this->integer('client_id')) {
                $fail('That therapist invoice is for a different client.');
            }
        };
    }
}
