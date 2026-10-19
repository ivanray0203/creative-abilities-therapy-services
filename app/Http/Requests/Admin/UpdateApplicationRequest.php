<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin "Edit Application" form: every detail the candidate submitted on
 * the public career form, minus the resume and cover letter uploads.
 *
 * Only the identity and contact fields are required — older applications
 * predate some of the public form's required fields, and an admin fixing a
 * typo should not be forced to invent the rest.
 */
class UpdateApplicationRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'resident_status' => ['nullable', 'string', 'max:200'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'position_applied' => ['required', 'string', 'max:255'],
            'position_id' => ['nullable', 'integer', 'exists:careers,id'],
            'profession_status' => ['nullable', 'string', 'max:255'],
            'preferred_start_date' => ['nullable', 'date'],
            'is_working_with_other' => ['required', 'boolean'],
            'availability' => ['nullable', 'array'],
            'availability.*.week_day' => ['required', 'string'],
            'availability.*.time_from' => ['nullable', 'string'],
            'availability.*.time_to' => ['nullable', 'string'],
            'availability_notes' => ['nullable', 'string', 'max:1000'],
            'drivers_license' => ['required', 'boolean'],
            'has_vehicle' => ['required', 'boolean'],
            'lead_source' => ['nullable', 'string', 'max:255'],
            'reason_for_applying' => ['nullable', 'string'],
            'other_notes' => ['nullable', 'string'],
            'expected_salary' => ['nullable', 'string', 'max:255'],
            'experience' => ['nullable', 'string'],
            'education' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['required', 'string'],
            'references' => ['nullable', 'array'],
            'references.*.full_name' => ['nullable', 'string', 'max:255'],
            'references.*.position' => ['nullable', 'string', 'max:255'],
            'references.*.work' => ['nullable', 'string', 'max:255'],
            'references.*.email' => ['nullable', 'email', 'max:255'],
            'references.*.phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
