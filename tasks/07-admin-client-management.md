# Phase 7 — Admin Client Management

## Goal
Port the admin client list/detail experience, including all 7 detail tabs and care-team/service management.

## Pages to port
- `admin/ClientsPage.tsx` — client cards, stats (total/active/upcoming/paused), funding overview, search + status + funding filters.
- `admin/ClientDetailPage.tsx` — header, reassign-therapist button (`AssignTherapistModal`), add-service button (`AddClientService` modal), edit button, summary cards (child info/approval date/services/progress %), tabs:
  - `clientTabs/Overview.tsx` — child/parent/emergency/assigned-therapist cards, medical history, current+requested services with `ServiceModal` popup, availability.
  - `clientTabs/Sessions.tsx` — `ServiceCard` per availed service, links to `ServiceSessions` page (Phase 8).
  - `clientTabs/Documents.tsx` — same upload/view/delete pattern as intake documents.
  - `clientTabs/Funding.tsx` — FSCD/Insurance/Private funding detail cards, contract dates; "funding history" stays a stub ("Coming Soon") per reference.
  - `clientTabs/Invoices.tsx` — shared `InvoicesTable` filtered by client (component built in Phase 9).
  - `clientTabs/Notes.tsx` — clinical notes add/delete (JSON blob per Phase 2 decision).
  - `clientTabs/Progress.tsx` — stub "Coming Soon" — do not build out.
  - `clientTabs/Therapist.tsx` — care-team list + `AddTherapistModal`.
- `components/ClientForm.tsx` (admin edit) — intake fields (read via `original_intake`) + client-specific `status` enum + contract dates, printable (`react-to-print` → use `dompdf`/browser print equivalent).

## Actions to implement (mirror `ClientViewSet` custom actions)
- `POST clients/{id}/assign-therapist` — sets `assigned_therapist`/`primary_therapist`.
- `POST clients/{id}/reassign-therapist` — calls `reassignPrimaryTherapist()` model method (writes timeline entry, updates care team).
- `PATCH clients/{id}/care-team` — add/remove secondary therapists.
- `client_services` CRUD (`AddClientService` modal) — creates/edits `ClientService` rows, updates `Client.service_availed` denormalized cache.

## Acceptance criteria
- All 7 tabs render with correct data scoped to the client.
- Reassigning primary therapist updates care team and appends a timeline entry identical in shape to the reference.
- Stub tabs (Progress, Funding history) remain visibly "Coming Soon" — not implemented.
