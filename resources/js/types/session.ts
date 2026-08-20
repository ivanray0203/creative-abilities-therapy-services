/**
 * Shared admin/therapist scheduling shapes, matching `ScheduleSession`
 * (app/Models/ScheduleSession.php) plus its eager-loaded relations.
 */

import type { Client, ClientService, ServiceOffering } from '@/types/client';
import type { TherapistOption } from '@/types/intake';

export type ScheduleSessionStatus =
    | 'scheduled'
    | 'completed'
    | 'cancelled'
    | 'inprogress'
    | 'confirmed'
    | 'no_show'
    | 'pending'
    | 'disputed';

export type QuickFilter = 'all' | 'today' | 'upcoming' | 'past' | 'disputed';

export interface ScheduleSession {
    id: number;
    client_id: number;
    therapist_id: number;
    service_id: number | null;
    service_name: string | null;
    scheduled_start: string;
    scheduled_end: string;
    location: string | null;
    /** Minutes. */
    duration: number | null;
    notes: string | null;
    status: ScheduleSessionStatus;
    elapsed_time: string | null;
    start_time: string | null;
    end_time: string | null;
    cancel_reason: string | null;
    dispute_reason: string | null;
    created_at: string;
    updated_at: string;
    client?: Client | null;
    therapist?: TherapistOption | null;
    service?: ServiceOffering | null;
    /** The availed services this one visit delivers. */
    client_services?: ClientService[];
}

/**
 * A session flattened for the admin calendar grid
 * (Admin\CalendarController::sessions()). Dates and times arrive
 * pre-formatted in the app timezone so the grid never re-parses them.
 */
export interface CalendarSession {
    id: string;
    /** `YYYY-MM-DD`. */
    date: string;
    /** `HH:MM`. */
    time: string;
    endTime: string;
    client: string;
    /** The service name as configured, so not a fixed set of codes. */
    type: string;
    location: string;
    status: string;
    therapistId: number | null;
    therapist: string;
}

export interface SessionStats {
    total: number;
    today: number;
    upcoming: number;
    disputed: number;
}

export interface SessionFilters {
    quick: QuickFilter;
    search: string;
    therapist_id: string | null;
    service_id: string | null;
    status: string;
}

export type { Paginated } from '@/types/intake';
