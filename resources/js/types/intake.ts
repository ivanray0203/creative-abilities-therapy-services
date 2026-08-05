/**
 * Admin intake pipeline shapes, matching the `Intake` Eloquent model's JSON
 * cast shape (app/Models/Intake.php) plus the `status_label`/`status_variant`
 * badge attributes IntakeController resolves server-side (replacing
 * cats-frontend's client-side `getStatusBadge` merge).
 */

export type IntakeStatus =
    'pending' | 'under_review' | 'waitlist' | 'approved' | 'denied';

export type IntakeStatusVariant =
    IntakeStatus | 'therapist_pending' | 'therapist_rejected';

export interface IntakeTimelineEntry {
    id: string;
    title: string;
    date: string;
    time: string;
}

export interface IntakeNote {
    id: string;
    note: string;
    date: string;
    time: string;
    user: string;
}

export interface IntakeDocument {
    id: number;
    intake_id: number;
    name: string;
    type: string;
    file: string | null;
    drive_file_url: string | null;
    drive_web_view: string | null;
    uploaded_at: string;
}

export interface TherapistOption {
    id: number;
    first_name: string;
    last_name: string;
    email?: string;
    specializations?: string[];
}

export interface IntakeTherapistReview {
    id: number;
    intake_id: number;
    therapist_id: number;
    service: string | null;
    status: 'pending' | 'approved' | 'rejected' | 'reassign';
    notes: string | null;
    decided_at: string | null;
    therapist?: TherapistOption | null;
}

export interface IntakeFundingSourceInfo {
    FSCD_case_worker_name?: string;
    FSCD_case_worker_email?: string;
    FSCD_approval_start_date?: string;
    FSCD_approval_end_date?: string;
    insurance_provider?: string;
    policy_number?: string;
    certificate_number?: string;
    policy_holder_name?: string;
    policy_holder_date_of_birth?: string;
    pre_authorization_obtained?: string;
    used_annual_maximum?: string;
    authorization_start_date?: string;
    authorization_end_date?: string;
    consents?: { title: string; datetime: string }[];
}

export interface Intake {
    id: number;
    reference_number: string | null;
    child_first_name: string;
    child_middle_name: string | null;
    child_last_name: string;
    date_of_birth: string;
    age: number;
    gender: string | null;
    status: IntakeStatus;
    street_address: string | null;
    address_line_2: string | null;
    city: string | null;
    state_province: string | null;
    postal_code: string | null;
    grade_level: string | null;
    school_name: string | null;
    services_needed: string[] | null;
    currently_receiving_services: boolean;
    receiving_services_desc: string | null;
    diagnosis: string[] | null;
    has_medical_conditions: boolean;
    languages_spoken_at_home: string | null;
    require_interpreter: boolean;
    interpreter_needed: string | null;
    medical_conditions: string | null;
    theraphy_goals: string | null;
    admin_addition_informations: string | null;
    funding_source: string;
    funding_source_info: IntakeFundingSourceInfo | null;
    available_days: string[] | null;
    preferred_times: string[] | null;
    primary_parent_name: string;
    primary_parent_phone: string | null;
    primary_parent_email: string | null;
    primary_relationship_to_child: string | null;
    primary_contact_method: string | null;
    secondary_parent_name: string | null;
    secondary_parent_phone: string | null;
    secondary_parent_email: string | null;
    secondary_relationship_to_child: string | null;
    secondary_contact_method: string | null;
    additional_information: string | null;
    referral_source: string | null;
    emergency_contact_name: string | null;
    emergency_contact_relationship: string | null;
    emergency_contact_phone: string | null;
    completed: boolean;
    reviewed: boolean;
    approved_as_client: boolean;
    linked_client_id: string | null;
    assigned_therapist_id: number | null;
    assigned_at: string | null;
    notes: IntakeNote[] | null;
    timeline: IntakeTimelineEntry[] | null;
    created_at: string;
    updated_at: string;
    documents?: IntakeDocument[];
    therapist_reviews?: IntakeTherapistReview[];
    therapist_review_history?: IntakeTherapistReview[];
    status_label: string;
    status_short_label: string;
    status_variant: IntakeStatusVariant;
}

export interface IntakeStats {
    pending: number;
    under_review: number;
    waitlist: number;
    denied: number;
    fscd: number;
    insurance: number;
    private: number;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
