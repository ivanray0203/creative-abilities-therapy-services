/**
 * Admin team-member management shapes, matching the `TeamMember` Eloquent
 * model (app/Models/TeamMember.php) plus its eager-loaded relations.
 */

import type { AvailabilityEntry } from '@/types/application';
import type { Client } from '@/types/client';
import type { Paginated } from '@/types/intake';
import type { InvoiceService, InvoiceServiceRate } from '@/types/invoice';
import type { ScheduleSession } from '@/types/session';

export type Department =
    'clinical_services' | 'administration' | 'finance' | 'operations';

export type EmploymentStatus =
    'active' | 'inactive' | 'on_leave' | 'terminated' | 'archived';

export interface TeamMemberUser {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    full_name?: string;
}

export interface TeamMemberDocument {
    id: number;
    doc_type: string | null;
    title: string | null;
    drive_file_url: string | null;
    drive_web_view: string | null;
    uploaded_at: string;
}

export interface TeamMember {
    id: number;
    user_id: number;
    position: string | null;
    resident_status: string | null;
    department: Department;
    employment_status: EmploymentStatus;
    hire_date: string | null;
    hourly_rate: string | null;
    maximum_caseload: number;
    credentials: string[] | null;
    specializations: string[] | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    can_access_finance: boolean;
    can_manage_team: boolean;
    can_manage_clients: boolean;
    additional_notes: string | null;
    title: string | null;
    description: string | null;
    photo: string | null;
    client: number[] | null;
    application_id: number | null;
    phone: string | null;
    office_phone: string | null;
    street_address: string | null;
    address_line_2: string | null;
    city: string | null;
    province: string | null;
    zip_code: string | null;
    availability: AvailabilityEntry[] | null;
    documents: number[] | null;
    secondary_email: string | null;
    birthdate: string | null;
    required_documents: string[] | null;
    sin_number: string | null;
    license_number: string | null;
    years_of_experience: string | null;
    created_at: string;
    updated_at: string;
    user?: TeamMemberUser;
    caseload?: number;
}

export interface TeamMemberStats {
    total: number;
    active: number;
    caseload: number;
    avg_caseload: number;
}

export interface TeamMemberShowProps {
    teamMember: TeamMember;
    caseload: number;
    clients: Client[];
    recentSessions: ScheduleSession[];
    documents: TeamMemberDocument[];
    missingDocuments: string[];
    invoiceServices: InvoiceService[];
    /** Keyed by invoice service id; a missing key means no override. */
    invoiceServiceRates: Record<number, InvoiceServiceRate>;
}

export type { Paginated };
