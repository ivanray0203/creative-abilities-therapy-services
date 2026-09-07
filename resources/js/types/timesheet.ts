/**
 * Aide timesheet shapes, matching `Timesheet` and `TimesheetEntry`
 * (app/Models/) plus their eager-loaded relations.
 *
 * The aide's counterpart to the billing types: an entry is one day's hours
 * for one child, and it stays loose until a generated timesheet claims it.
 * Nothing here carries a rate — the form records hours only.
 */

import type { Client } from '@/types/client';
import type { TherapistOption } from '@/types/intake';

export type TimesheetStatus = 'awaiting_client' | 'signed';

export type HoursStatusFilter = 'all' | 'unsheeted' | 'sheeted';

/** One day's hours for one child, as logged by the aide. */
export interface TimesheetEntry {
    id: number;
    therapist_id: number;
    client_id: number;
    entry_date: string;
    /** Hours are fractional: 0.25 and 1.5 are real quantities. */
    hourly_respite_hours: string;
    community_support_hours: string;
    bda_direct_hours: string;
    bda_indirect_hours: string;
    notes: string | null;
    /** Filled in only once a generated timesheet claims this day. */
    timesheet_id: number | null;
    created_at: string;
    updated_at: string;
    client?: Client | null;
    timesheet?: { id: number; timesheet_number: string } | null;
}

/** A claimed day as frozen onto the generated form. */
export interface TimesheetRow {
    date: string | null;
    hourly_respite: number;
    community_support: number;
    bda_direct: number;
    bda_indirect: number;
    notes?: string | null;
}

export interface TimesheetTimelineEntry {
    id: string;
    title: string;
    date: string;
    time: string;
}

export interface Timesheet {
    id: number;
    timesheet_number: string;
    therapist_id: number;
    client_id: number;
    issued_by_id: number | null;
    period_start: string;
    period_end: string;
    rows: TimesheetRow[] | null;
    total_hourly_respite: string;
    total_community_support: string;
    total_bda_direct: string;
    total_bda_indirect: string;
    total_hours: string;
    /** PNG data URIs drawn on the signature pad. */
    aide_signature: string | null;
    parent_signature: string | null;
    aide_signed_at: string | null;
    parent_signed_at: string | null;
    status: TimesheetStatus;
    /** Drive web-view URLs for the filed copies. */
    not_signed_timesheet: string | null;
    signed_timesheet: string | null;
    timeline: TimesheetTimelineEntry[] | null;
    created_at: string;
    updated_at: string;
    client?: Client | null;
    therapist?: TherapistOption | null;
}

export interface HoursStats {
    unsheeted_hours: number;
    unsheeted_days: number;
    sheeted_hours: number;
    this_month_hours: number;
}

export interface HoursFilters {
    search: string;
    status: HoursStatusFilter | string;
}

export interface TimesheetStats {
    awaiting_client: number;
    signed: number;
    total_hours: number;
}

export interface TimesheetFilters {
    search: string;
    status: TimesheetStatus | 'all' | string;
}

/**
 * The four columns of the printed form, in the order it prints them. Shared
 * by the entry form, the ledger table and the on-screen timesheet so all
 * three read the same way.
 */
export const HOUR_COLUMNS = [
    {
        entryKey: 'hourly_respite_hours',
        rowKey: 'hourly_respite',
        label: 'Hourly Respite',
    },
    {
        entryKey: 'community_support_hours',
        rowKey: 'community_support',
        label: 'Community Support Aide',
    },
    {
        entryKey: 'bda_direct_hours',
        rowKey: 'bda_direct',
        label: 'Direct Hours',
    },
    {
        entryKey: 'bda_indirect_hours',
        rowKey: 'bda_indirect',
        label: 'Indirect Hours',
    },
] as const satisfies ReadonlyArray<{
    entryKey: keyof Pick<
        TimesheetEntry,
        | 'hourly_respite_hours'
        | 'community_support_hours'
        | 'bda_direct_hours'
        | 'bda_indirect_hours'
    >;
    rowKey: keyof Pick<
        TimesheetRow,
        'hourly_respite' | 'community_support' | 'bda_direct' | 'bda_indirect'
    >;
    label: string;
}>;

/**
 * The columns that make up "Total Hours": direct and indirect aide support.
 * Respite and community-support hours are logged and totalled per column but
 * stay out of the grand total.
 */
export const TOTAL_COLUMNS = HOUR_COLUMNS.filter(
    (column) =>
        column.rowKey === 'bda_direct' || column.rowKey === 'bda_indirect',
);
