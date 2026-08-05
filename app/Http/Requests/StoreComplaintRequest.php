<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared client/therapist "File a Complaint" form (reference:
 * cats-frontend/src/forms/ComplaintForm.tsx).
 *
 * `client_id`/`therapist_id`/`complained_by` are never accepted here — the
 * controller forces all three from the acting user, same pattern as
 * StoreSessionRequest.
 */
class StoreComplaintRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    public const CATEGORIES = ['scheduling', 'billing', 'quality', 'communication', 'other'];

    public function authorize(): bool
    {
        return $this->user()?->isTherapist() === true || $this->user()?->isClient() === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:schedule_sessions,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'consent_given' => ['required', 'accepted'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ];
    }
}
