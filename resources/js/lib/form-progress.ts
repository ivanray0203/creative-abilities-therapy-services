/**
 * Reusable percent-over-required-fields progress calculator, mirroring the
 * inline `watch` subscription logic in cats-frontend's
 * src/forms/CareerApplicationFrom.tsx (and reused by the intake form).
 */
export interface FormProgressResult {
    progress: number;
    missingFields: string[];
}

/** Guarded so this stays safe wherever `Blob` is not defined (e.g. SSR). */
function isFileLike(value: unknown): value is Blob {
    return typeof Blob !== 'undefined' && value instanceof Blob;
}

/**
 * @param values Current form values.
 * @param requiredFields Dot-path field names, e.g. "references.0.email", resolved against `values`.
 */
export function computeFormProgress(
    values: object,
    requiredFields: string[],
): FormProgressResult {
    let filled = 0;
    const missingFields: string[] = [];

    requiredFields.forEach((field) => {
        const value = field
            .split('.')
            .reduce<unknown>(
                (obj, key) =>
                    (obj as Record<string, unknown> | undefined)?.[key],
                values as Record<string, unknown>,
            );

        let isFilled: boolean;

        if (Array.isArray(value)) {
            isFilled = value.length > 0;
        } else if (isFileLike(value)) {
            // File extends Blob, and carries its data on the prototype rather
            // than as own keys — so the object branch below would read a
            // perfectly good upload as empty and report it still missing.
            isFilled = value.size > 0;
        } else if (typeof value === 'object' && value !== null) {
            // Grid-shaped answers (e.g. availability_slots) are objects; an
            // empty one means nothing was picked, not "answered".
            isFilled = Object.keys(value).length > 0;
        } else {
            isFilled = value !== undefined && value !== null && value !== '';
        }

        if (isFilled) {
            filled += 1;
        } else {
            missingFields.push(field);
        }
    });

    return {
        progress:
            requiredFields.length > 0
                ? Math.round((filled / requiredFields.length) * 100)
                : 0,
        missingFields,
    };
}

const INTAKE_BASE_REQUIRED_FIELDS = [
    'child_first_name',
    'child_last_name',
    'date_of_birth',
    'gender',
    'street_address',
    'city',
    'state_province',
    'postal_code',
    'grade_level',
    'school_name',
    'primary_parent_name',
    'primary_parent_phone',
    'primary_parent_email',
    'primary_parent_email_confirm',
    'primary_relationship_to_child',
    'primary_contact_method',
    'emergency_contact_name',
    'emergency_contact_phone',
    'emergency_contact_relationship',
    'diagnosis',
    'has_medical_conditions',
    'languages_spoken_at_home',
    'funding_source',
    'services_needed',
    'currently_receiving_services',
    'availability_slots',
    'referral_source',
];

const FSCD_FUNDING_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

/**
 * Intake-form progress calculator, mirroring IntakeApplicationForm.tsx's
 * calculateProgress(): the fixed field list above, plus fscd_info/
 * insurance_info sub-fields when the matching funding source is selected,
 * plus (for FSCD sources) the three hardcoded FSCD consent checkboxes,
 * which aren't form fields so are passed in separately.
 */
export function computeIntakeProgress(
    values: object,
    fscdConsents: readonly [boolean, boolean, boolean] = [false, false, false],
): FormProgressResult {
    const fundingSource = (values as Record<string, unknown>).funding_source as
        string | undefined;
    const fields = [...INTAKE_BASE_REQUIRED_FIELDS];

    const diagnosis = (values as Record<string, unknown>).diagnosis;

    if (Array.isArray(diagnosis) && diagnosis.includes('Other')) {
        fields.push('diagnosis_other');
    }

    if (fundingSource && FSCD_FUNDING_SOURCES.includes(fundingSource)) {
        fields.push(
            'fscd_info.FSCD_case_worker_name',
            'fscd_info.FSCD_case_worker_email',
            'fscd_info.FSCD_approval_start_date',
        );
    }

    if (fundingSource === 'Insurance') {
        fields.push(
            'insurance_info.insurance_provider',
            'insurance_info.policy_holder_name',
            'insurance_info.policy_number',
            'insurance_info.certificate_number',
            'insurance_info.policy_holder_date_of_birth',
            'insurance_info.pre_authorization_obtained',
        );
    }

    const { progress: fieldProgress, missingFields } = computeFormProgress(
        values,
        fields,
    );

    const externalFields =
        fundingSource && FSCD_FUNDING_SOURCES.includes(fundingSource)
            ? [
                  { value: fscdConsents[0], key: 'FSCD Consent 1' },
                  { value: fscdConsents[1], key: 'FSCD Consent 2' },
                  { value: fscdConsents[2], key: 'FSCD Consent 3' },
              ]
            : [];

    if (externalFields.length === 0) {
        return { progress: fieldProgress, missingFields };
    }

    const filledFieldCount = Math.round((fieldProgress / 100) * fields.length);
    const filledExternal = externalFields.filter((f) => f.value).length;
    const missingExternal = externalFields
        .filter((f) => !f.value)
        .map((f) => f.key);

    const total = fields.length + externalFields.length;
    const progress =
        total > 0
            ? Math.round(((filledFieldCount + filledExternal) / total) * 100)
            : 0;

    return { progress, missingFields: [...missingFields, ...missingExternal] };
}
