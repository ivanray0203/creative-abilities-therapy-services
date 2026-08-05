# Phase 3 — Authentication & Roles

## Goal
Port the reference's auth flows to Laravel session/cookie auth, preserving the same role model and endpoints' behavior (not the JWT mechanism itself — Inertia monoliths don't need JWT, use standard Laravel session auth).

## Reference behavior to preserve
- Email-based login (no username).
- Roles: admin, therapist, client, staff, guest — stored as a `role` column, checked ad hoc (mirror with `EnsureRole` middleware + simple `$user->role === 'admin'` checks, not a full permissions package the reference doesn't have).
- `AuthMeView` equivalent: an endpoint/shared Inertia prop that returns current user + team_member profile (for therapists) + client_id (for clients) on every page load, matching `AuthContext`'s `user`, `clientID`, `teamAvail`.
- Register (admin-created or public — confirm which flows the reference actually exposes via `RegisterView`; it's used both for client self-registration during intake promotion and admin-created team members).
- Forgot password → emailed reset link with `uid`/`token` query params → `ResetPassword.tsx` page → `ResetPasswordConfirmView`.
- Change password (self-service, requires current+new+confirm).
- Therapist listing endpoint (`GET therapists/`) — used by admin forms (assign therapist dropdowns).
- Post-login redirect by role: admin → `/admin/intake`, therapist → `/therapist`, client → `/client/calendar` (not `/client` dashboard — reference sends client to calendar since dashboard is a stub).
- Role-mismatch redirect: if a logged-in user hits a route outside their role, redirect to their own role home instead of erroring.

## Tasks
- `routes/web.php` auth routes: login, logout, register (admin-only creation of staff/therapist accounts — verify against reference's actual usage), forgot-password, reset-password.
- `App\Http\Middleware\EnsureRole` (params: allowed roles) applied to `/admin/*`, `/therapist/*`, `/client/*` route groups.
- Shared Inertia data (`HandleInertiaRequests::share`) exposing `auth.user`, `auth.team_member`, `auth.client_id` — replacement for `AuthMeView`/`AuthContext`.
- `resources/js/pages/auth/ResetPassword.tsx` — reads `token`/`uid`/`email` from URL, submits new password.
- Password reset email (Phase 15 owns the mail driver decision; this phase just triggers `Notification`/`Mailable` with reset link).
- `TeamMemberController` listing endpoint for therapist dropdowns (`role = therapist`).
- Consent acceptance table/endpoints scaffolding only (`user_consent_acceptances` CRUD used later by intake form in Phase 5) — full consent document CMS content is out of scope here, just the accept/revoke/list endpoints.

## Explicitly not building
- JWT tokens/cookies — Laravel session auth replaces this entirely; there's no product requirement for JWT itself, only for "stay logged in" behavior which session auth + remember-me covers.
- Django Groups/permissions — reference doesn't use them either.

## Acceptance criteria
- Login/logout/forgot/reset password work end to end for a seeded user of each role.
- Visiting a route outside your role redirects to your role's home page (Pest feature tests per role).
- Shared Inertia props expose the same shape `AuthContext` consumers expect (user, team_member, client_id).
