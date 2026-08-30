/**
 * Admin client pipeline shapes, matching the `Client` Eloquent model
 * (app/Models/Client.php) plus its eager-loaded relations.
 */

import type { Intake, TherapistOption } from '@/types/intake';
import type { Invoice } from '@/types/invoice';

export type ClientStatus =
    'active' | 'paused' | 'completed' | 'inactive' | 'archive';

export interface ClientNote {
    id: string;
    note: string;
    date: string;
    time: string;
    user: string;
}

export interface ClientTimelineEntry {
    id: string;
    action: string;
    previous_therapist_id: number | null;
    new_therapist_id: number;
    changed_by: number;
    date: string;
}

export interface ServiceOffering {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    type: string | null;
    base_price: string | null;
}

export type ServiceContractStatus =
    'active' | 'exhausted' | 'expired' | 'cancelled';

/**
 * Admin's authorization for one availed service: a pool of hours over a
 * fixed period, matching `ServiceContract` (app/Models/ServiceContract.php).
 *
 * `remaining_hours` and `status` are computed server-side rather than read
 * from columns — the balance is summed from the session ledger, and the
 * status shown is the live one rather than whatever the nightly sweep last
 * wrote.
 */
export interface ServiceContractSummary {
    id: number;
    contract_number: string;
    allotted_hours: number;
    remaining_hours: number;
    period_start: string | null;
    period_end: string | null;
    status: ServiceContractStatus;
    notes: string | null;
}

/**
 * The covering contract as the session form sees it, hung on each availed
 * service by SessionController::attachContractSummaries().
 */
export interface BookableContract {
    id: number;
    contract_number: string;
    allotted_hours: number;
    remaining_hours: number;
    period_start: string | null;
    period_end: string | null;
}

/**
 * A contract as the admin client page reads it, straight off the model.
 *
 * `remaining_hours` and `derived_status` are appended server-side by
 * Admin\ClientController::attachContractBalances(): the balance is summed
 * from the session ledger, and the status is the live one rather than
 * whatever the nightly sweep last wrote to the column.
 */
export interface ServiceContract {
    id: number;
    contract_number: string;
    client_service_id: number;
    therapist_id: number | null;
    issued_by_id: number | null;
    /** Decimal cast, so a string on the wire. */
    allotted_hours: string;
    /** Which funder pays for these hours; null until an admin sets it. */
    funding_code: string | null;
    period_start: string;
    period_end: string;
    status: ServiceContractStatus;
    notes: string | null;
    remaining_hours: number;
    derived_status: ServiceContractStatus;
}

export interface ClientService {
    id: number;
    /**
     * Phase 21 — the funding code the Issue Contract form opens on, from the
     * funder already recorded for the service or the child. A suggestion the
     * admin can change or clear, not a value the server enforces.
     */
    default_funding_code?: string | null;
    client_id: number;
    service_id: number;
    therapist_id: number | null;
    frequency: string | null;
    duration: string | null;
    start_date: string | null;
    funding_source: string | null;
    no_sessions: number;
    goals: string | null;
    created_at: string;
    updated_at: string;
    service?: ServiceOffering | null;
    therapist?: TherapistOption | null;
    /** Session pickers only: the contract covering the day being booked. */
    contract?: BookableContract | null;
    /** Admin client page: every contract issued against this service. */
    contracts?: ServiceContract[];
    /** Set when the service arrives through a session's ledger rows. */
    pivot?: { hours: string | number; service_contract_id: number | null };
}

export interface ServiceAvailedEntry {
    id: number;
    service_id: number;
    service_name: string | null;
    therapist_id: number | null;
    therapist_name: string | null;
    frequency: string | null;
    duration: string | null;
    start_date: string | null;
    funding_source: string | null;
    no_sessions: number;
    goals: string | null;
    contracts: ServiceContractSummary[];
}

export interface ClientDocument {
    id: number;
    client_id: number;
    doc_type: string | null;
    title: string | null;
    upload_origin: 'admin' | 'therapist' | 'client';
    drive_file_id: string | null;
    drive_file_url: string | null;
    drive_web_view: string | null;
    notes: string | null;
    uploaded_by_id: number | null;
    uploaded_at: string;
}

export interface Client {
    id: number;
    original_intake_id: number | null;
    primary_therapist_id: number | null;
    user_id: number | null;
    assigned_therapist_id: number | null;
    assigned_at: string | null;
    approved_date: string;
    clinical_notes: ClientNote[] | null;
    active_services: string | null;
    allergies: string[] | null;
    contract_start_date: string | null;
    contract_end_date: string | null;
    signed_date: string | null;
    timeline: ClientTimelineEntry[] | null;
    status: ClientStatus;
    consents: number[] | null;
    service_availed: ServiceAvailedEntry[] | null;
    created_at: string;
    updated_at: string;
    original_intake?: Intake | null;
    assigned_therapist?: TherapistOption | null;
    primary_therapist?: TherapistOption | null;
    care_team?: TherapistOption[];
    client_services?: ClientService[];
    /**
     * Therapist caseload only: whether this therapist holds a contracted
     * service of this client's with hours left today.
     */
    has_bookable_service?: boolean;
    documents?: ClientDocument[];
    invoices?: Invoice[];
}

export interface ClientStats {
    total: number;
    active: number;
    paused: number;
    upcoming: number;
    fscd: number;
    insurance: number;
    private: number;
}

export type { Paginated } from '@/types/intake';

/** Per-availed-service delivery, for the client Progress tab. */
export interface ClientServiceProgress {
    id: number;
    name: string;
    therapist: string | null;
    frequency: string | null;
    goals: string | null;
    /** Sessions authorised. Null when none was recorded on the service. */
    authorised: number | null;
    delivered: number;
    remaining: number | null;
    percent: number | null;
    hours: number;
}

export interface ClientProgress {
    services: ClientServiceProgress[];
    attendance: {
        attended: number;
        cancelled: number;
        no_show: number;
        /** Null until there is an attended or missed session to measure. */
        rate: number | null;
    };
    hours: { total: number; this_month: number };
    has_data: boolean;
}
