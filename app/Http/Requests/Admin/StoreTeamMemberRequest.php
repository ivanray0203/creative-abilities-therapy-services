<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Admin "Add/Edit Team Member" form (reference:
 * cats-frontend/src/components/TeamMemberForm.tsx).
 *
 * `StoreTeamMemberRequest` additionally validates the fields needed to
 * create the paired `User` account; `UpdateTeamMemberRequest` drops those.
 */
class StoreTeamMemberRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    public const EMPLOYMENT_STATUSES = ['onboarding', 'active', 'inactive', 'on_leave', 'terminated', 'archived'];

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
            ...$this->userRules(),
            ...$this->teamMemberRules(),
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function userRules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function teamMemberRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'office_phone' => ['nullable', 'string', 'max:20'],
            'secondary_email' => ['nullable', 'email', 'max:255'],
            'birthdate' => ['nullable', 'date'],
            'sin_number' => ['nullable', 'string', 'max:20'],
            'street_address' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'resident_status' => ['required', 'string', 'max:200'],

            'position' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department' => ['nullable', Rule::in(['clinical_services', 'administration', 'finance', 'operations'])],
            'employment_status' => ['required', Rule::in(self::EMPLOYMENT_STATUSES)],
            'hire_date' => ['required', 'date'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'maximum_caseload' => ['required', 'integer', 'min:0'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'years_of_experience' => ['nullable', 'string', 'max:50'],

            'availability' => ['required', 'array', 'size:7'],
            'availability.*.week_day' => ['required', 'string'],
            'availability.*.time_from' => ['nullable', 'string'],
            'availability.*.time_to' => ['nullable', 'string'],

            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['string'],
            'specializations' => ['nullable', 'array'],
            'specializations.*' => ['string'],

            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],

            'can_access_finance' => ['boolean'],
            'can_manage_team' => ['boolean'],
            'can_manage_clients' => ['boolean'],

            'additional_notes' => ['nullable', 'string'],
        ];
    }
}
