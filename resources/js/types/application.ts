/**
 * Admin hiring pipeline shapes, matching the `Application` Eloquent model
 * (app/Models/Application.php).
 */

export type ApplicationStatus =
    | 'pending'
    | 'reviewing'
    | 'interview_scheduled'
    | 'offer_sent'
    | 'onboarding'
    | 'hired'
    | 'declined';

export interface ApplicationNote {
    id: string;
    note: string;
    date: string;
    time: string;
    user: string;
}

export interface AvailabilityEntry {
    week_day: string;
    time_from: string | null;
    time_to: string | null;
}

export interface Application {
    id: number;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    phone: string;
    email: string;
    street_address: string | null;
    address_line_2: string | null;
    city: string | null;
    province: string | null;
    zip_code: string | null;
    position_applied: string;
    position_id: number | null;
    profession_status: string | null;
    preferred_start_date: string | null;
    is_working_with_other: boolean;
    resume: string | null;
    cover_letter: string | null;
    drivers_license: boolean;
    has_vehicle: boolean;
    lead_source: string | null;
    reason_for_applying: string | null;
    other_notes: string | null;
    application_status: ApplicationStatus;
    internal_notes: ApplicationNote[] | null;
    notes: string[] | null;
    experience: string | null;
    expected_salary: string | null;
    notice_availability: string | null;
    hourly_rate: string | null;
    hire_date: string | null;
    interview_date: string | null;
    interview_time: string | null;
    interview_platform: string | null;
    /** Google Meet link for a video interview; null when none was generated. */
    interview_meeting_link: string | null;
    /**
     * Server-rendered labels, appended by the Application model so the admin
     * screens and the candidate's email read the same string — and so the
     * clock never passes through the viewer's timezone.
     */
    interview_schedule: string | null;
    interview_platform_label: string | null;
    education: string | null;
    skills: string[] | null;
    candidate_rating: number | null;
    hired: boolean;
    declined: boolean;
    availability: AvailabilityEntry[] | null;
    references: Record<string, unknown>[] | null;
    resident_status: string | null;
    reference_number: string | null;
    offer_sent_at: string | null;
    offer_expires_at: string | null;
    offer_letter: string | null;
    signed_offer_letter: string | null;
    offer_accepted_at: string | null;
    offer_declined_at: string | null;
    onboarding_started_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface ApplicationStats {
    total: number;
    pending: number;
    reviewing: number;
    interview_scheduled: number;
    offer_sent: number;
    onboarding: number;
    hired: number;
    declined: number;
}

/** One document the candidate uploaded through their profile during onboarding. */
export interface OnboardingDocument {
    id: number;
    title: string | null;
    doc_type: string | null;
    drive_web_view: string | null;
    uploaded_at: string | null;
}

/**
 * The checklist an admin reviews before hiring: what the position requires,
 * what has been uploaded, and what is still outstanding. Null until the
 * candidate has been moved to onboarding.
 */
export interface ApplicationOnboarding {
    required_documents: string[];
    missing_documents: string[];
    documents: OnboardingDocument[];
}

export type { Paginated } from '@/types/intake';
