<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared admin/therapist "Add Session" form (reference: cats-frontend/src/forms/SessionsForm.tsx).
 *
 * `therapist_id` is only required from admins — a therapist submitting this
 * form is always scheduling for themselves, enforced in the controller
 * rather than here since it depends on the acting user.
 */
class StoreSessionRequest extends FormRequest
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
            'therapist_id' => [$this->user()?->isAdmin() === true ? 'required' : 'nullable', 'integer', 'exists:users,id'],
            'linked_client_service_id' => ['nullable', 'integer', 'exists:client_services,id'],
            'service_id' => ['nullable', 'integer', 'exists:service_offerings,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
