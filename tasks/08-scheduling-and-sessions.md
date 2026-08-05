# Phase 8 — Scheduling & Sessions

## Goal
Port the shared scheduling system used by both admin and therapist roles, plus the per-client-service session list page.

## Pages/components to port
- `admin/SessionssPage.tsx` (shared by admin & therapist, role-aware) — quick filters (All/Today/Upcoming/Past/Disputed), advanced filters (therapist/service/status/search), stats cards, Day/Week/Month tabs (`SessionsDailyView`/`SessionsWeeklyView`/`SessionsMonthlyView`), card/table view toggle, CSV export, `SessionListModal` (day cell with >1 session).
- `forms/SessionsForm.tsx` (shared, `/sessions/add`, `/sessions/edit/:id`) — client/therapist/linked-client-service/service selects, location, date/time → computed `scheduled_start`/`scheduled_end`, duration enum, notes; shows client vs therapist availability side by side.
- `pages/ServiceSessions.tsx` — paginated/searchable/status-filterable session list scoped to one `ClientService` record (route: `.../clients/sessions/:serviceTitle/:clientName/:id`).
- `modals/SessionCardModal.tsx` — full session detail popup, reschedule/cancel actions.
- `modals/CancelConfirmationModal.tsx` — confirm + reason for cancelling.

## Decision needed before building
- Reference has a **separate dummy `admin/CalendarPage.tsx`** with hardcoded data, unreachable from the sidebar (nav link commented out). Confirm with user: skip entirely (recommended, it's dead/unfinished in reference) vs port as another stub.

## Endpoints to implement (mirror `ScheduleSessionViewSet`)
- Full CRUD.
- `GET sessions/by_user`, `GET sessions/by_user_and_service`, `GET sessions/by_client_service`, `GET sessions/therapist/{user_id}`.
- Status transitions: scheduled → inprogress → completed, plus cancelled/no_show/disputed paths (dispute path connects to Complaints, Phase 12/13/14).

## Business logic to port
- `session.helpers.ts` equivalents: `handleStartSession`/`handleEndSession` (used by therapist active-session tracking, Phase 13), `handleVerify`/`handleDispute` (client-side, Phase 14 — dispute auto-creates a linked `Complaint` with `type=disputes`), `STATUS_COLORS` mapping, elapsed-time tracking.
- `getScheduleMatchDetails` (day/time overlap between intake/client availability and therapist availability → none/partial/full) — used here and in Phase 13's intake review.

## Acceptance criteria
- Creating a session from the shared form correctly computes start/end from date+time+duration.
- Day/Week/Month views render the same session set with correct filtering.
- Cancelling/disputing a session updates status and (for dispute) creates the linked complaint.
