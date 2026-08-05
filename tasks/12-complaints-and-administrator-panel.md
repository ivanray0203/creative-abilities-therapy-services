# Phase 12 — Complaints/Disputes & Administrator Panel

## Goal
Port complaint/dispute resolution and the Administrator-only settings/audit panel.

## 12.1 Complaints & Disputes
- `admin/MessagesPage.tsx` ("Complaints & Disputes") — stats (new/under review/total), tabs, cards per complaint/dispute with session details, `ComplaintModal` (view detail incl. session info, attachment, resolution), `ResolveModal` (resolve with resolution text), "Start Review" action (`handleStartReview` helper).
- Shared `ComplaintForm.tsx`/`ComplaintList.tsx` components (also used by therapist Phase 13 and client Phase 14 to file complaints) — role-dependent auto-fill of client/therapist/session, subject, category enum, description (min 10 chars), consent checkbox (must be true), optional file attachment.
- Endpoints (mirror `ComplaintViewSet`, role-scoped queryset — admin sees all, therapist/client see only their own).

## 12.2 Administrator Panel
- `admin/AdministratorPage.tsx` — **desktop-only** (mobile shows a blocking notice — preserve this constraint), tabs:
  - `administratorTabs/SystemLogsTab.tsx` — searchable/filterable/paginated audit log table, stats (success/error/warning/info), CSV export. Reads `system_logs` table populated by the `LogAction()` equivalent (Phase 16).
  - `administratorTabs/AdminUserTabs.tsx` — admin user table, add/edit via `AddAdminUserModal`, deactivate action.
  - `administratorTabs/ServicesTab.tsx` — public-website service visibility toggles (`is_active` switch on `services` table) — directly affects the public Services page from Phase 4.
  - `administratorTabs/SettingsTab.tsx` — own profile edit form, notification preference toggles (`new_intake`/`invoice_payments`/`session_reminders`/`new_applications`), `ChangePassCard`.

## Acceptance criteria
- Complaint/dispute filing works from all three roles with correct auto-fill and role-scoped visibility.
- Resolving a complaint records `admin_response`, `reviewed_at`/`reviewed_by`, updates status.
- Administrator panel is inaccessible/blocked on mobile viewport, matching reference.
- Toggling a service's `is_active` in the Services tab is reflected on the public Services page without a deploy.
