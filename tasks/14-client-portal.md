# Phase 14 — Client Portal

## Goal
Port the minimal client/parent self-service portal.

## Pages to port
- `client/Dashboard.tsx` — stub "Coming Soon" (do not build out; note that post-login redirect sends clients straight to `/client/calendar`, not this dashboard — see Phase 3).
- `client/CalendarPage.tsx` — date picker, status filter chips (All/Scheduled/Pending/Verified/Disputed/Cancelled), disputed/pending alert banners, session cards with Verify/Dispute actions (`VerifySessionModal`/`DisputeSessionModal`), `SessionCardModal` detail view.
- `client/InvoicesPage.tsx` — card/table toggle, summary (paid/pending/overdue), search, view → shared `InvoiceDetailPage` (Phase 9) with client-appropriate action gating (no edit/mark-paid).
- `client/ReportsPage.tsx` — stub "Coming Soon", do not build out.
- `client/ComplaintsPage.tsx` — shared `ComplaintForm`/`ComplaintList` (Phase 12), client-scoped, `complained_by = client`.
- `client/ProfilePage.tsx` — tabs Profile Information (`ClientProfileForm` — child name, email disabled, phone, DOB, address, emergency contact) / Security (`ChangePassCard`) / Documents (reuses the admin `clientTabs/Documents` component, read-focused for clients).

## Business logic to port
- Session verify/dispute (`handleVerify`/`handleDispute` from Phase 8) — dispute auto-creates a linked `Complaint` with `type = disputes`, `complained_by = client`.

## Acceptance criteria
- A client can verify or dispute a completed session; disputing creates the linked complaint record.
- Client cannot access admin/therapist-only actions on shared components (invoice edit, mark-as-paid, etc.) — enforce via Phase 3's role middleware/policies, not just UI hiding.
- Profile edits persist correctly, email field stays disabled/non-editable matching reference.
