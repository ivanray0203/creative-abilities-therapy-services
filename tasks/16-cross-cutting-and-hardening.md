# Phase 16 — Cross-Cutting Concerns & Hardening

## Goal
Port the concerns that apply across every feature built in Phases 3–14, plus final test/deployment prep.

## 16.1 Audit logging (`LogAction()` equivalent)
- In the reference, **every** create/update/delete/login mutation across the entire app writes a `system_logs` row (action, details json with status/user_email/module/detail, ip_address).
- Port as a Laravel event listener or a trait/service (`AuditLogger::log($action, $details)`) called from every controller action that mutates state — do this as a sweep across all phases' controllers once they exist, not as new ad hoc calls per feature.
- Feeds the `administratorTabs/SystemLogsTab.tsx` from Phase 12.

## 16.2 Notification preferences
- `User` has `new_intake`, `invoice_payments`, `session_reminders`, `new_applications` boolean prefs (edited in Settings tab, Phase 12). Ensure the actual notification-sending code (Phase 15 emails) checks these flags before sending, matching reference intent even if the reference's own wiring of these flags to send-logic should be double-checked during port (verify in reference views whether these flags are actually consulted before sending, or just stored — port whatever the reference actually does, don't add enforcement it doesn't have).

## 16.3 Scheduler
- Consolidate all cron-equivalents in `routes/console.php`: daily overdue-invoice job (Phase 9). Reference has no other scheduled jobs — don't invent more.

## 16.4 Testing
- Pest feature tests per phase's acceptance criteria (auth/role redirects, intake→client promotion, invoice totals math, session status transitions, complaint/dispute linkage, document upload/delete, consent acceptance flow).
- Factories for every model (built in Phase 2) exercised by these tests.
- `vendor/bin/pint --dirty --format agent` and `phpstan`/larastan clean before considering any phase done.

## 16.5 Deployment prep
- Environment config for storage/mail/Mailcow credentials decided in Phase 15.
- `.env.example` updated with every new config key introduced across phases.
- Confirm production checklist (queue worker running for mailbox/email jobs, scheduler cron entry, storage disk permissions).

## Acceptance criteria
- Every mutating endpoint across the app produces a `system_logs` row.
- Full Pest suite passes.
- `.env.example` fully documents required configuration for a fresh environment to run the app.
