import type { Intake } from '@/types/intake';

/**
 * Client-side CSV export for a single intake, ported from
 * cats-frontend/src/pages/admin/IntakeDetailPage.tsx `handleExport` — same
 * column set and array-joining conventions. Cells are quoted (the reference
 * joins raw, which corrupts any value containing a comma).
 */
const HEADERS = [
    'Reference Number',
    'Child First Name',
    'Child Last Name',
    'Date of Birth',
    'Age',
    'Gender',
    'Diagnosis',
    'Available Days',
    'Preferred Times',
    'School Name',
    'Primary Parent Name',
    'Primary Parent Email',
    'Primary Parent Phone',
    'Address',
    'City',
    'State/Province',
    'Postal Code',
    'Funding Source',
    'Services Needed',
    'Medical Conditions',
    'Interpreter Needed',
    'Notes',
];

function escapeCell(value: string | number | null | undefined): string {
    return `"${String(value ?? '').replace(/"/g, '""')}"`;
}

export function exportIntakeCsv(intake: Intake): void {
    const row = [
        intake.reference_number,
        intake.child_first_name,
        intake.child_last_name,
        intake.date_of_birth,
        intake.age,
        intake.gender,
        (intake.diagnosis ?? []).join('; '),
        (intake.available_days ?? []).join(', '),
        (intake.preferred_times ?? []).join(', '),
        intake.school_name,
        intake.primary_parent_name,
        intake.primary_parent_email,
        intake.primary_parent_phone,
        intake.street_address,
        intake.city,
        intake.state_province,
        intake.postal_code,
        intake.funding_source,
        (intake.services_needed ?? []).join('; '),
        intake.medical_conditions,
        intake.require_interpreter ? 'Yes' : 'No',
        (intake.notes ?? []).map((note) => note.note).join('; '),
    ];

    const csvContent = [
        HEADERS.map(escapeCell).join(','),
        row.map(escapeCell).join(','),
    ].join('\n');

    const link = document.createElement('a');
    link.href = `data:text/csv;charset=utf-8,${encodeURIComponent(csvContent)}`;
    link.download = 'Intake_Records.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
