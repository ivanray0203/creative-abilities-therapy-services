# Phase 5 — Public Intake & Career Application Forms

## Goal
Port the two large public-facing forms exactly, including their validation rules, conditional fields, draft auto-save, and consent flow.

## 5.1 Intake Application (`/intake/apply`)
Source: `pages/IntakeApplication.tsx` wrapping `forms/IntakeApplicationForm.tsx`.

- Fields: child info, cascading province/city address (`Provinces`/`ProvinceCities`), education, primary/secondary parent (email-confirm match via refine-equivalent), emergency contact, medical/diagnosis checkboxes, funding source branches (FSCD schema, Insurance schema, Private — conditionally required based on `funding_source`), services needed (from `servicesMap`), availability days/times, referral source, consent checkboxes tied to live `consent_documents` (purpose=intake).
- Validation: mirror the zod rules — required fields, dual-email match, conditional funding sub-schema requiredness, progress % calculation over required fields.
- Debounced duplicate-email check (`check-user` equivalent endpoint).
- Draft auto-save to `localStorage`, restorable on mount (manual "Save for Later"), gated behind cookie consent.
- `IntakePreviewModal` before final submit, `MissingFieldsModal` for incomplete required fields, `IntakeSubmittedModal` on success.
- `ConsentModal` — renders `consent_clauses` for the current `consent_documents` version with placeholder substitution, records acceptance via `user_consent_acceptances` (AllowAny/public endpoint per reference).
- On submit: creates an `Intake` row with `status = pending`, generates `reference_number` (`INT-{year}-{seq}`).

## 5.2 Career Application (`/careers/apply`, `/careers/apply/:id`)
Source: `pages/CareerApplication.tsx` wrapping `forms/CareerApplicationFrom.tsx`.

- Fields: applicant name/contact/address, position, profession status, 7-day availability array, resident status, resume/cover-letter file upload (required), skills (tag input, min 1), education, experience, expected salary, lead source, drivers license/vehicle, references (min 1, each field required), reason for applying, consent checkboxes.
- Same draft-autosave + debounced email-exists check + progress tracker pattern as intake form.
- `ApplicationPreviewModal`, `MissingFieldsModal`, `ApplicationSubmittedModal`.
- On submit: creates an `Application` row, `application_status = pending`, `reference_number` (`APP-{year}-{seq}`), file uploads handled per Phase 15's storage decision.

## Shared infra needed
- File upload endpoint(s) for resume/cover letter (multipart) and intake documents.
- Reference-number generator service (built in Phase 2, used here).
- Consent acceptance endpoints (built in Phase 3, consumed here).

## Acceptance criteria
- Submitting each form end-to-end creates the correct DB rows with correct reference numbers.
- Conditional required-field logic matches reference exactly (test each funding_source branch).
- Draft save/restore works via localStorage same as reference.
