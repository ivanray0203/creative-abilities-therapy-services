# Phase 11 — Team Management

## Goal
Port admin team-member management: list, detail (5 tabs), form, and compliance checks.

## Pages to port
- `admin/TeamPage.tsx` — team member cards with caseload progress bar, stats (total/active/caseload/avg caseload), CSV export, filters by role (from `Careers` position list) and status.
- `admin/TeamMemberDetailPage.tsx` — header, "Manage Access and Status" modal (`ManageTeamAccesModal` — permissions + employment status), summary cards (caseload real; sessions completed/rating/doc-rate stay "Coming Soon" per reference), tabs:
  - `teamMemberTabs/OverviewTeamMember.tsx` — contact/employment info incl. **masked SIN** (`maskSIN` helper — never display the hash/raw value in full), credentials/specializations, performance metrics placeholder.
  - `teamMemberTabs/ClientTab.tsx` — assigned clients list.
  - `teamMemberTabs/SessionsTab.tsx` — `SessionListCard` of therapist's sessions.
  - `teamMemberTabs/ScheduleTab.tsx` — weekly availability display.
  - `teamMemberTabs/DocumentsTab.tsx` — missing-required-docs warning banner (compares uploaded docs against `Career.required_documents` for the member's position), upload/list/delete.
- `components/TeamMemberForm.tsx` — personal info (incl. masked SIN input, birthdate), employment info (position dropdown from Careers data, hire date, hourly rate, max caseload, employment status), 7-day availability grid, credentials (tag input) + specializations (checkboxes sourced from `allServices`), emergency contact, system permission switches, additional notes.

## Endpoints (mirror `TeamMemberViewSet` + `UserViewSet` admin actions)
- Full CRUD, `GET/PATCH team-members/me`, `POST team-members/create`.
- `GET users/admin-list`, `PATCH users/{id}/admin-update`.
- SIN storage: one-way hash on write (SHA-256, matching reference — never store/display plaintext), masked display via helper.

## Acceptance criteria
- SIN is never returned in plaintext from any endpoint or rendered unmasked in the UI.
- Missing-required-docs banner correctly diffs uploaded docs vs the position's `required_documents`.
- Permission switches (finance/team-mgmt/client-mgmt) gate the relevant admin UI/endpoints per Phase 3's role middleware.
