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

export interface InvoiceLineItem {
    name: string;
    description: string | null;
    period: string;
    numberOfSessions: number;
    rate: string;
    rate_numeric: number;
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
