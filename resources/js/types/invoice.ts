/**
 * Shared admin/therapist/client invoicing shapes, matching `Invoice`
 * (app/Models/Invoice.php) plus its eager-loaded relations.
 */

import type { Client } from '@/types/client';
import type { TherapistOption } from '@/types/intake';
import type { ScheduleSession } from '@/types/session';

export type InvoiceStatus =
    'sent' | 'draft' | 'paid' | 'overdue' | 'unpaid' | 'refunded';

export type BilledBy = 'therapist' | 'admin';

export type QuickInvoiceFilter = 'all' | 'paid' | 'unpaid' | 'overdue';

/** A line on the published invoice rate card (app/Models/InvoiceService.php). */
export interface InvoiceService {
    id: number;
    name: string;
    code: string;
    discipline: string;
    rate_fscd: string | null;
    rate_private: string | null;
    is_active?: boolean;
    sort_order?: number;
}

/** One team member's override of a rate line; a null rate falls back to the card. */
export interface InvoiceServiceRate {
    rate_fscd: string | null;
    rate_private: string | null;
}

/**
 * A rate-card line offered by the invoice form, already priced for whoever
 * is raising the invoice: the therapist's own rates when they bill, the
 * clinic's published rates when an admin does.
 */
export interface InvoiceServiceOption {
    id: number;
    name: string;
    code: string;
    discipline: string;
    rate_fscd: string | null;
    rate_private: string | null;
}

export interface InvoiceLineItem {
    /** The rate-card line this came from; null for a hand-typed "Other" line. */
    invoice_service_id?: number | null;
    name: string;
    description: string | null;
    period: string;
    /** Billable hours, so fractional: 0.75 and 1.5 are real quantities. */
    numberOfSessions: number;
    rate: string;
    rate_numeric: number;
    /** Monthly statement lines only: the date the bill was raised. */
    date?: string | null;
    /** Monthly statement lines only: the child the work was for. */
    client?: string | null;
}

export interface InvoiceTimelineEntry {
    id: string;
    title: string;
    date: string;
    time: string;
}

export interface Invoice {
    id: number;
    client_id: number;
    therapist_id: number | null;
    billing_account_id: number | null;
    session_id: number | null;
    reference: string | null;
    invoice_id: string | null;
    services: InvoiceLineItem[];
    sub_total: string;
    tax_percentage: string;
    /** Drive URL of the invoice as issued, before the parent signs it. */
    not_signed_invoice: string | null;
    /** Drive URL of the copy the parent signed and returned. Null until then. */
    signed_invoice: string | null;
    gst: string;
    total: string;
    amount_due: string;
    invoice_date: string | null;
    due_date: string | null;
    paid_at: string | null;
    paid_date: string | null;
    status: InvoiceStatus;
    processed_by: string | null;
    issued_by_id: number | null;
    notes: string | null;
    timeline: InvoiceTimelineEntry[] | null;
    bill_to_name: string | null;
    bill_to_email: string | null;
    bill_to_phone: string | null;
    bill_to_address: string | null;
    billed_by: BilledBy | null;
    linked_therapist_invoice_id: number | null;
    /** True on the therapist's month-end statement to the clinic. */
    is_monthly: boolean;
    /** The calendar month a monthly statement covers. */
    period_start: string | null;
    period_end: string | null;
    /** On a therapist's client bill: the statement that rolled it up. */
    monthly_invoice_id: number | null;
    created_at: string;
    updated_at: string;
    client?: Client | null;
    therapist?: TherapistOption | null;
    session?: ScheduleSession | null;
    linked_therapist_invoice?: Invoice | null;
    linked_admin_invoices?: Invoice[];
}

export interface InvoiceStats {
    paid_this_month: number;
    pending: number;
    overdue: number;
    total_revenue: number;
    /** Outstanding therapist bills. Admin-only — null for everyone else. */
    owed_to_therapists: number | null;
}

export interface InvoiceFilters {
    quick: QuickInvoiceFilter;
    search: string;
    status: string;
    /** `all`, or a `billed_by` value. Admin-only; always `all` otherwise. */
    direction: string;
}

export type { Paginated } from '@/types/intake';
