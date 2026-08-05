# Phase 10 — Hiring & Applications

## Goal
Port the job-application review pipeline from submission (Phase 5) through hire, including the offer letter and company mailbox provisioning side effects.

## Pages to port
- `admin/ApplicationsPage.tsx` — list/cards with rating stars, status filters, `UpdateApplicationStatus` modal (reviewing/interview_scheduled/hired/declined transitions, interview date/time/platform, hourly rate on hire).
- `admin/ApplicationDetailPage.tsx` — header with avatar/rating, interview/hired banners, tabs:
  - `applicationTabs/OverviewApplication.tsx` — contact + professional info.
  - `applicationTabs/ApplicationTab.tsx` — why-join, lead source, resume/cover-letter download links.
  - `applicationTabs/AvailabilityTab.tsx` — weekly availability grid.
  - `applicationTabs/ApplicationNotesTab.tsx` — internal notes.

## Endpoints (mirror `ApplicationViewSet`)
- Full CRUD; public create already covered in Phase 5.
- Status transition side effects to preserve:
  - `hired = true` → triggers Mailcow mailbox auto-provisioning (Phase 15) and offer-letter PDF generation/send (Phase 15).
  - Confirm with user whether "hired" should also auto-create a `TeamMember`/`User` record (reference's `Application`/`TeamMember` link is via `TeamMember.application_id` FK — check whether creation is automatic or a manual follow-up admin action in the reference before assuming).

## Note on Careers data source
Per Phase 4's decision, the public Careers listing is now DB-backed (`careers` table) instead of static JSON. `Application.position_applied`/`position_id` should reference real `careers` rows, and the admin Applications UI's position filter should read from the same table (replacing the reference's static `json/careers.tsx` `Careers` array as the filter source).

## Acceptance criteria
- Status pipeline (pending → reviewing → interview_scheduled → hired/declined) matches reference transitions and required fields at each step.
- Hiring an applicant triggers the mailbox + offer letter flows (can be stubbed/queued pending Phase 15 credentials, but the trigger points must exist here).
