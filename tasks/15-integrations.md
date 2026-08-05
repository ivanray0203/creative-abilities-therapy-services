# Phase 15 — External Integrations

## Goal
Replace the reference's Python-specific integration *code* with Laravel equivalents while **keeping the same third-party services** (Google Drive, Gmail API, Mailcow) — confirmed decision: keep, don't swap.

## 15.1 Document storage — Google Drive (keep)
- Reference: `upload_to_google_drive_structured()` builds `SharedDrive > target_type > "target_name - YYYY-MM-DD" > file` using a **service account** + Shared Drive, returns `drive_file_id`/`drive_file_url`/`drive_web_view`, used for ALL uploads (intake docs, client docs, complaints, application resumes/cover letters, team member photos, consent PDFs).
- Build a Laravel service class (`GoogleDriveService`) using the `google/apiclient` PHP package with the same service account JSON + `SHARED_DRIVE_ID` env var, replicating: folder creation/lookup (`get_or_create_folder`), the same folder-naming convention, upload, and delete-with-trash-fallback (`delete_from_google_drive`).
- Every document-upload feature (Phases 5–14) writes to `drive_file_id`/`drive_file_url`/`drive_web_view` columns via this one service — no per-feature ad hoc upload code.

## 15.2 Email sending — Gmail API (keep)
- Reference sends from `noreply-cats@creativeabilitiestherapyservices.ca` via **Gmail API using an OAuth2 user token** (`token_cats.json`), not SMTP; fire-and-forget via a background thread. Used for: intake-therapist-approval emails, welcome email w/ generated password on client promotion, password reset, offer letters, contact form notifications.
- Build a custom Laravel Mail transport (or a `GmailMailer` service wrapping `google/apiclient`'s Gmail scope) that sends via the same Gmail API + OAuth token, but replace the raw background-thread pattern with **queued Mailables** (Laravel queue) for reliability/retries instead of fire-and-forget.
- Reuse the existing OAuth token generation approach (one-off script analogous to reference's `generate_token_gmail.py`) to obtain the refresh token for this Laravel app's own credentials.

## 15.3 Mailcow mailbox auto-provisioning (keep)
- Reference: on `Application.hired = true`, generates `firstname.lastname@domain` mailbox via Mailcow API with retry/backoff, fire-and-forget thread.
- Port as a queued Laravel Job (`CreateMailcowMailbox`) using the HTTP client, config-driven (`MAILCOW_API_URL`, `MAILCOW_API_KEY`, `MAILCOW_DEFAULT_DOMAIN`), same retry/backoff behavior, triggered from Phase 10's hire-status transition.

## 15.4 PDF generation (ReportLab → Laravel PDF library)
- `generate_offer_letter_pdf(application)` — offer letter with logo, +5 business-day deadline calc.
- `generate_consent_pdf`/`generate_consent_pdf_for_intake` — multi-document consent PDF (cover page + per-document clauses) from `UserConsentAcceptance` records, uploaded to Google Drive (via 15.1) same as reference.
- Port with `barryvdh/laravel-dompdf` or `spatie/laravel-pdf` — pick whichever renders the closest visual match to the reference's ReportLab layouts (evaluate both against the offer-letter/consent-PDF templates before committing).
- Intake/invoice PDF exports (Phases 6, 9) also depend on this same PDF service.

## 15.5 Reference-number generation
- Already scoped in Phase 2 — flagging again as shared infra: `INT-{year}-{seq}` / `APP-{year}-{seq}`, must be race-condition-safe (reference's per-year-count approach is not — use `lockForUpdate` or a counter table in the Laravel port).

## Acceptance criteria
- Google Drive, Gmail API, and Mailcow are the live integrations in the rebuilt app — no SMTP/S3 substitution.
- All document uploads across every phase route through one `GoogleDriveService`, not ad hoc per-feature code.
- All outbound emails route through Laravel's queued Mailables + the Gmail API transport, replacing fire-and-forget threads with proper queue jobs (retryable, observable).
- Mailcow provisioning and PDF generation (offer letter, consent PDF, intake/invoice exports) match reference content and are wired to the same trigger points (hire status, intake approval, invoice/intake export actions).
