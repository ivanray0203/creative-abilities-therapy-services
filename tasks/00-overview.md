# CATS Rebuild — Master Plan (Django+React → Laravel+Inertia+React)

## Source of truth

This rebuild is a **1:1 port**, not a redesign. No new features, no customization beyond what already exists in:

- **Backend reference**: `C:\laragon\www\cats-backend` (Django + DRF + Postgres)

> **Database note**: the reference backend uses Postgres; this rebuild targets **MySQL**. All schema/type notes below are adjusted accordingly (e.g. no `ArrayField`, no Postgres-specific JSON operators — use MySQL's `JSON` column type and Eloquent's array/JSON casts throughout).
- **Frontend reference**: `C:\laragon\www\cats-frontend` (Vite + React + TS, shadcn/ui, approved client design)

Every page, tab, modal, form, field, endpoint, and business rule in those two repos must exist in the rebuilt app. If something is a stub ("Coming Soon") in the reference, it stays a stub here — do not build it out further than the reference does. If something is dead/unreachable code in the reference (e.g. `Dashboard.tsx` early-return stubs, commented-out sidebar links, unused `Index.tsx`, unused JSON files), do not port it — only port what is actually reachable/used.

## Stack mapping

| Django/React concept | Laravel/Inertia equivalent |
|---|---|
| DRF serializers/viewsets | Form Requests + Eloquent + Inertia controllers (or API resource controllers where the frontend needs JSON, e.g. polling/React Query-style fetches) |
| JWT-in-httpOnly-cookie auth | Laravel session/cookie auth (Fortify or hand-rolled) — SPA-in-monolith via Inertia does not need JWT; use standard Laravel session auth with `role` on the `users` table |
| `role` field ad hoc checks | Laravel Gate/Policy per role, or simple middleware `EnsureUserHasRole` mirroring the reference's ad hoc checks (do not invent a permissions package the reference doesn't have) |
| React Router v6 | Inertia pages under `resources/js/pages`, Laravel routes in `routes/web.php` |
| React Query | Inertia props (server-driven) for page data; keep client-side polling only where the reference actually polls (active session, calendar) |
| Google Drive (service account) storage | Laravel filesystem custom driver wrapping Google Drive API, OR keep local/S3 disk behind the same `ClientDocument`-style abstraction — decide with user before Phase 15 |
| Gmail API OAuth email | Laravel Mail (SMTP or Gmail API driver) — decide with user before Phase 15 |
| Mailcow API mailbox provisioning | Laravel HTTP client service class, queued job |
| ReportLab PDFs | `barryvdh/laravel-dompdf` or `spatie/laravel-pdf` |
| Celery beat (`mark_invoices_overdue`) | Laravel Scheduler (`routes/console.php` `Schedule::command(...)->daily()`) |
| Postgres `ArrayField` (`Application.skills`) | MySQL `JSON` column |
| JSON "blob" fields for notes/timeline (client-generated IDs) | Decide: keep as JSON columns (fastest port) vs normalize into real tables (cleaner) — default to JSON columns to match reference behavior exactly, note as a possible follow-up |

## Phases

1. `01-project-setup.md` — Laravel/Inertia/React scaffolding, design tokens, shared UI kit port (shadcn equivalents), base layouts
2. `02-database-schema.md` — All migrations + Eloquent models for every Django model
3. `03-auth-and-roles.md` — Register/login/logout/forgot-password/reset-password, `AuthMe` equivalent, role middleware, consent acceptance flow foundation
4. `04-public-marketing-site.md` — Home, About, Team, Founder, Services, ServiceDetail, Fscd, Careers, CareerDetail, Faq, legal pages, Contact form, 404
5. `05-public-intake-and-career-forms.md` — Public Intake Application form + consent flow, Career Application form, preview/submitted modals, draft auto-save
6. `06-admin-intake-management.md` — Admin Intake list/detail/tabs, approve/send-to-therapist/therapist-approve-reject, PDF export, documents
7. `07-admin-client-management.md` — Admin Clients list/detail/tabs (Overview/Clinical Notes/Funding/Sessions/Documents/Therapist/Invoices), care team, service assignment
8. `08-scheduling-and-sessions.md` — Sessions page (admin+therapist shared), SessionsForm, calendar day/week/month views, ServiceSessions page
9. `09-invoicing-and-billing.md` — Invoices list/detail/form, dual therapist/admin invoicing, PDF, resend, mark-as-paid, overdue scheduled job
10. `10-hiring-and-applications.md` — Applications list/detail/tabs, status pipeline, hire flow, Mailcow mailbox provisioning, offer letter PDF
11. `11-team-management.md` — Team list/detail/tabs, TeamMemberForm, permissions, availability, documents, missing-docs compliance
12. `12-complaints-and-administrator-panel.md` — Complaints & Disputes, Administrator panel (System Logs, Admin Users, public Services visibility toggles, Settings)
13. `13-therapist-portal.md` — Therapist Dashboard, Calendar, Clients, Complaints, Intake review/decision, Profile, active-session tracking
14. `14-client-portal.md` — Client Calendar (verify/dispute), Invoices, Complaints, Profile
15. `15-integrations.md` — Document storage (Drive or alternative), email sending, Mailcow, PDF generation, consent PDF assembly
16. `16-cross-cutting-and-hardening.md` — System-log audit trail on every mutation, notification preferences, scheduler jobs, tests, deployment prep
17. `17-multi-child-client-model.md` — Post-port refactor: one parent with many children, each as its own client record (not part of the parity port)
18. `18-scheduling-conflicts-review-queue-authorization.md` — Post-port hardening: double-booking prevention, therapist review index, policy-based authorization (not part of the parity port)

## Ground rules for every phase

- Confirm field names/enums against `02-database-schema.md` before building forms — don't invent fields.
- Match the reference's role-gating exactly (admin/therapist/client) via middleware, not per-component checks scattered in Blade/React.
- Reuse one shared component per duplicated reference component (e.g. `InvoicesTable`, `InvoiceDetailPage`, `SessionsForm`, `DocumentsTab` are shared across admin/therapist/client in the reference — keep them shared in the rebuild too).
- Every write action must call the audit-log equivalent of `LogAction()` (see Phase 16), matching reference behavior.
- Stub pages stay stubs ("Coming Soon") unless the user asks to build them out — this is a parity port, not a feature add.
