/**
 * Admin hiring pipeline shapes, matching the `Application` Eloquent model
 * (app/Models/Application.php).
 */

export type ApplicationStatus =
    | 'pending'
    | 'reviewing'
    | 'interview_scheduled'
    | 'shortlisted'
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
    education: string | null;
    skills: string[] | null;
    candidate_rating: number | null;
    hired: boolean;
    declined: boolean;
    availability: AvailabilityEntry[] | null;
    references: Record<string, unknown>[] | null;
    resident_status: string | null;
    reference_number: string | null;
    created_at: string;
    updated_at: string;
}

export interface ApplicationStats {
    total: number;
    pending: number;
    reviewing: number;
    interview_scheduled: number;
    hired: number;
    declined: number;
}

export type { Paginated } from '@/types/intake';
