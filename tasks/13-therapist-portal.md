# Phase 13 — Therapist Portal

## Goal
Port the therapist-facing experience: dashboard, calendar, clients, intake review/decision, complaints, profile, and the cross-page active-session tracker.

## Pages to port
- `therapist/Dashboard.tsx` — welcome header w/ live clock, stats (today's sessions; completed-this-week/hours stay "Coming Soon" per reference), pending intake-review approvals section (accept/decline), `ActiveSessionCard`, today's schedule list with Start Session button.
- `therapist/CalendarPage.tsx` — date picker + tabs (Upcoming/Pending/Completed) for the selected date, `ActiveSessionCard`, schedule button.
- `therapist/ClientsPage.tsx` — active/inactive tabs, search, `ClientCard` list, `ClientScheduleModal`.
- `therapist/ComplaintsPage.tsx` — shared `ComplaintForm`/`ComplaintList` from Phase 12, therapist-scoped.
- `therapist/IntakeDetailPageTherapist.tsx` — capacity card, schedule-match card (`getScheduleMatchDetails` from Phase 8), specialization-match badges, tabs (`intakeTabs/OverviewIntakeTherapist.tsx`, `ScheduleTab.tsx` day/time overlap visualization, `TherapistIntakeDocumentsTab.tsx` read-only), and — when a decision is pending — an accept/decline panel calling `intakes/{id}/therapist-approve` / `therapist-reject` (Phase 6 endpoints).
- `therapist/ProfilePage.tsx` — tabs Profile Information (`TherapistProfileForm`) / Security (`ChangePassCard`) / Documents (shared `DocumentsTab`).
- `therapist/ReportsPage.tsx` — stays a "Coming Soon" stub, do not build out.

## Active-session tracking (`ActiveSessionContext` equivalent)
- Global (within therapist layout) state tracking any session with `status = inprogress` for the logged-in therapist.
- `startSession`/`endSession` actions PATCH the session status and refresh dependent views (Inertia partial reloads or a small client-side store — match reference's cross-page persistence behavior, i.e. the active session banner/card must show on Dashboard and Calendar simultaneously).

## Acceptance criteria
- A therapist can start a session from Dashboard, see it reflected as active on Calendar, and end it from either page.
- Intake accept/decline correctly transitions `IntakeTherapistApproval.status` and writes to `IntakeTherapistApprovalHistory`.
- Schedule-match visualization produces the same none/partial/full result as Phase 8's shared helper.
