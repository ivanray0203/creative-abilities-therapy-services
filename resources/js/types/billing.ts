/**
 * Billing shapes, matching `BillingItem` (app/Models/BillingItem.php) plus
 * its eager-loaded relations. A billing item is one billed service line;
 * it stays unbilled until a month-end invoice claims it.
 */

import type { Client } from '@/types/client';
import type { TherapistOption } from '@/types/intake';
import type { ScheduleSession } from '@/types/session';

export type BillingStatusFilter = 'all' | 'unbilled' | 'billed';

export interface BillingItem {
    id: number;
    billing_number: string;
    therapist_id: number;
    client_id: number;
    session_id: number | null;
    /** The rate-card line this came from; null for a hand-typed "Other" line. */
    invoice_service_id: number | null;
    service_name: string;
    /** Billable hours, so fractional: 0.75 and 1.5 are real quantities. */
    quantity: string;
    rate: string;
    amount: string;
    /** Whoever raised the bill — the therapist, or an admin on their behalf. */
    issued_by_id: number | null;
    /** Filled in only once a generated invoice rolls this line into it. */
    invoice_id: number | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    client?: Client | null;
    therapist?: TherapistOption | null;
    session?: ScheduleSession | null;
    invoice?: { id: number; invoice_id: string | null } | null;
}

export interface BillingStats {
    unbilled_total: number;
    unbilled_count: number;
    billed_total: number;
    this_month_total: number;
}

export interface BillingFilters {
    search: string;
    status: BillingStatusFilter | string;
}
