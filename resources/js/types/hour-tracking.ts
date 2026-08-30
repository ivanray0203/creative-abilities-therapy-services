/**
 * Phase 21 — the therapist's hour-tracking sheet, built by
 * `App\Services\HourTrackingReport` (app/Services/HourTrackingReport.php).
 *
 * Everything here is derived server-side. The month cells and the remaining
 * figure are summed from the same session-ledger rows, so a row's months and
 * its balance can never disagree.
 */

/** One month column. `year` rides along so a window crossing New Year does not print "Jan" twice. */
export interface HourTrackingMonth {
    key: string;
    label: string;
    year: number;
}

export interface HourTrackingRow {
    contract_id: number;
    contract_number: string;
    client_name: string;
    /** Who the contract authorizes; only shown on the admin's clinic-wide sheet. */
    therapist_name: string;
    funding_code: string | null;
    period_start: string | null;
    period_end: string | null;
    allotted_hours: number;
    used_hours: number;
    remaining_hours: number;
    /** Keyed by month key; null is a month with no session, which reads as `x`. */
    months: Record<string, number | null>;
}

export interface HourTrackingSection {
    service: string;
    rows: HourTrackingRow[];
    allotted_hours: number;
    used_hours: number;
    remaining_hours: number;
}

export interface HourTrackingReport {
    months: HourTrackingMonth[];
    sections: HourTrackingSection[];
    totals: {
        allotted_hours: number;
        used_hours: number;
        remaining_hours: number;
    };
}

export interface HourTrackingFilters {
    from: string;
    to: string;
    /** Admin only. Null (or absent) is every therapist. */
    therapist_id?: number | null;
}

/** One entry in the admin's therapist filter. */
export interface HourTrackingTherapistOption {
    id: number;
    name: string;
}
