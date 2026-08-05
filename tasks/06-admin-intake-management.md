# Phase 6 — Admin Intake Management

## Goal
Port the admin intake pipeline: list, detail (5 tabs), status workflow, therapist assignment/review loop, PDF export.

## Pages to port
- `admin/IntakePage.tsx` — list of intakes not yet approved as clients. Stats cards (pending/under review/waitlist/denied), funding overview, search + funding filter, paginated table, status badges combining intake status + therapist pending-approval overlay (`getStatusBadge` logic).
- `admin/IntakeDetailPage.tsx` — header with status badge, status-change dropdown (pending→under_review→waitlist/approved/denied), CSV export, PDF export via `IntakePDFViewModal`, summary cards, tabs:
  - `intake/Overview.tsx` — child info, education, medical/diagnosis, services needed, availability, funding branches (FSCD/insurance/private), other programs, goals.
  - `intake/Family.tsx` — primary/secondary parent, emergency contact, address w/ Google Maps link.
  - `intake/Documents.tsx` — upload/view/download/delete via `UploadDocumentModal`/`DeleteDocumentModal`.
  - `intake/History.tsx` — timeline entries.
  - `intake/Notes.tsx` — add/delete internal notes.
- `components/IntakeForm.tsx` (admin add/edit) — same schema as public intake form plus status enum, documents sidebar, delete button, triggers `UpdateStatusModal`, `DeleteIntakeModal`.

## Workflow actions to implement (mirror `IntakeViewSet` custom actions)
- `POST intakes/{id}/approve` — admin direct-approve → calls `promote_intake_to_client` equivalent (creates/finds client `User`, creates `Client`, adds therapist to care team, creates `BillingAccount`, marks intake approved/reviewed/linked, sends welcome email with generated password if new account — see Phase 15 for email).
- `POST intakes/{id}/send-to-therapist` — creates `IntakeTherapistApproval` (status pending), assigns for review.
- `POST intakes/{id}/therapist-approve` / `therapist-reject` — used by therapist portal (Phase 13) but the admin-facing "therapist reviews" list lives here: `GET intakes/therapist-reviews` (admin view of all reviews, filterable by status/therapist).
- `POST intakes/multiple` — bulk file upload for an intake.
- `PATCH intakes/{id}/documents` — update/add intake documents.
- Split-name helper (`split_name`) for legacy free-text name fields if needed during promotion.

## Acceptance criteria
- Full intake lifecycle testable: submit (Phase 5) → appears in admin list → send to therapist → therapist decision (stub until Phase 13, but the admin-side approval endpoints must exist now) → admin approve → `Client` row created with correct linkage.
- PDF export renders the same sections as `IntakePDFViewModal` (Overview/Family/Documents/History/Notes).
- CSV export matches reference's column set.
