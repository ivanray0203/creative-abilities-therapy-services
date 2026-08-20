<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Admin "Add Intake" form (reference: cats-frontend/src/components/IntakeForm.tsx).
 *
 * Same field set as the public intake application, minus the terms/privacy
 * consents, plus the workflow `status` and `completed` flags only admins set.
 */
class StoreIntakeRequest extends FormRequest
{
    /**
     * FSCD funding-source variants that require the FSCD sub-form.
     *
     * @var array<int, string>
     */
    public const FSCD_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

    /**
     * Workflow statuses an intake may hold.
     *
     * @var array<int, string>
     */
    public const STATUSES = ['pending', 'under_review', 'waitlist', 'approved', 'denied'];

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $fundingSource = (string) $this->input('funding_source');
        $isFscd = in_array($fundingSource, self::FSCD_SOURCES, true);
        $isInsurance = $fundingSource === 'Insurance';

        return [
            'child_first_name' => ['required', 'string', 'max:100'],
            'child_middle_name' => ['nullable', 'string', 'max:100'],
            'child_last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'street_address' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state_province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:7'],
            'grade_level' => ['nullable', 'string', 'max:255'],
            'school_name' => ['nullable', 'string', 'max:255'],

            'services_needed' => ['array'],
            'services_needed.*' => ['string'],
            'currently_receiving_services' => ['boolean'],
            'receiving_services_desc' => ['nullable', 'string'],
            'diagnosis' => ['array'],
            'diagnosis.*' => ['string'],
            'diagnosis_other' => ['nullable', 'string', 'max:255'],
            'has_medical_conditions' => ['boolean'],
            'languages_spoken_at_home' => ['nullable', 'string', 'max:255'],
            'require_interpreter' => ['boolean'],
            'interpreter_needed' => ['nullable', 'string', 'max:255'],
            'medical_conditions' => ['nullable', 'string'],
            'theraphy_goals' => ['nullable', 'string', 'max:255'],
            'admin_addition_informations' => ['nullable', 'string', 'max:255'],

            'funding_source' => ['required', Rule::in([...self::FSCD_SOURCES, 'Insurance', 'private'])],
            'availability_slots' => ['array'],
            'availability_slots.*' => ['array'],
            'availability_slots.*.*' => ['string'],

            'primary_parent_name' => ['required', 'string', 'max:255'],
            'primary_parent_phone' => ['required', 'string', 'max:20'],
            'primary_parent_email' => ['required', 'email', 'max:255'],
            'primary_relationship_to_child' => ['required', 'string', 'max:255'],
            'primary_contact_method' => ['required', 'string', 'max:255'],

            'secondary_parent_name' => ['nullable', 'string', 'max:255'],
            'secondary_parent_phone' => ['nullable', 'string', 'max:20'],
            'secondary_parent_email' => ['nullable', 'email', 'max:255'],
            'secondary_relationship_to_child' => ['nullable', 'string', 'max:255'],
            'secondary_contact_method' => ['nullable', 'string', 'max:255'],

            'additional_information' => ['nullable', 'string'],
            'completed' => ['boolean'],
            'status' => ['nullable', Rule::in(self::STATUSES)],

            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],
            'emergency_contact_relationship' => ['required', 'string', 'max:255'],

            'fscd_info' => ['array'],
            'fscd_info.FSCD_case_worker_name' => [Rule::requiredIf($isFscd), 'nullable', 'string', 'max:255'],
            'fscd_info.FSCD_case_worker_email' => [Rule::requiredIf($isFscd), 'nullable', 'email', 'max:255'],
            'fscd_info.FSCD_approval_start_date' => [Rule::requiredIf($isFscd), 'nullable', 'date'],
            'fscd_info.FSCD_approval_end_date' => ['nullable', 'date'],

            'insurance_info' => ['array'],
            'insurance_info.insurance_provider' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:255'],
            'insurance_info.policy_number' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:255'],
            'insurance_info.certificate_number' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:255'],
            'insurance_info.policy_holder_name' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:255'],
            'insurance_info.policy_holder_date_of_birth' => [Rule::requiredIf($isInsurance), 'nullable', 'date'],
            'insurance_info.pre_authorization_obtained' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:255'],
            'insurance_info.used_annual_maximum' => ['nullable', 'string', 'max:255'],
            'insurance_info.authorization_start_date' => ['nullable', 'date'],
            'insurance_info.authorization_end_date' => ['nullable', 'date'],

            'referral_source' => ['required', 'string', 'max:255'],
            'referral_source_other' => ['nullable', 'string', 'max:255'],
        ];
    }
}
