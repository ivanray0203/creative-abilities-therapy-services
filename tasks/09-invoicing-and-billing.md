# Phase 9 — Invoicing & Billing

## Goal
Port the dual-tier invoicing system (therapist-billed-to-CATS and admin-billed-to-government/client, linked to each other) and the shared invoice UI used across all three roles.

## Pages/components to port
- `admin/InvoicesPage.tsx` (shared admin/therapist) — quick filters (all/paid/unpaid/overdue), summary cards (paid this month/pending/overdue/total revenue), advanced filters, `InvoicesTable`.
- `admin/InvoiceDetailPage.tsx` (shared admin/therapist/client, role-gated actions) — printable layout (logo, bill-to, services table, totals, activity timeline), print/export PDF, resend, mark-as-paid, edit/send (draft), link to client and linked therapist invoice.
- `forms/InvoiceForm.tsx` — client/therapist selects, invoice date/due date, tax %, notes, dynamic services line-items array (`useFieldArray` equivalent), live subtotal/tax/total computation, Save-as-Draft vs Save & Send.
- `modals/MarkAsPaidModal.tsx` — confirm mark-paid, also completes the linked session if applicable.
- `client/InvoicesPage.tsx` — card/table toggle, summary cards, search, read-only view into `InvoiceDetailPage`.

## Endpoints to implement (mirror `InvoiceViewSet`)
- Full CRUD.
- `POST invoices/generate_from_session` — auto-creates an invoice from a completed session.
- `POST invoices/{id}/resend`.
- `GET invoices/therapist/{user_id}`, `GET invoices/client/{user_id}`.
- `Invoice::calculateTotals()` — recompute sub_total/gst/total from `services` json line items (tax_percentage default 5.00).

## Scheduled job
- Daily job marking `status = sent` invoices past `due_date` as `overdue` (mirrors Celery beat `mark_invoices_overdue`), appending a timeline entry. Implement via `routes/console.php` `Schedule::command(...)->daily()`.

## Business rules to preserve
- `billed_by` enum (therapist/admin) and `linked_therapist_invoice` self-referencing FK — the two-tier billing relationship must round-trip correctly (creating/viewing one side shows the link to the other).
- CATS org "bill from" details come from config values (`CATS_NAME`, `CATS_PHONE`, `CATS_EMAIL`, `CATS_ADDRESS` in reference) — put these in Laravel config, not hardcoded in Blade/React.

## Acceptance criteria
- Creating an invoice with N line items computes totals identically to the reference formula.
- Marking an invoice paid also completes its linked session where applicable.
- Overdue job correctly transitions sent invoices past due_date.
- PDF/print output matches reference layout.
