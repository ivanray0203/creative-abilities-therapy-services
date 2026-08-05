# Phase 2 — Database Schema & Eloquent Models

## Goal
Recreate the final-state Django schema (not the migration history) as Laravel migrations + Eloquent models on **MySQL** (reference backend uses Postgres — this is a deliberate DB swap, not an oversight). Use the **current `models.py` field lists** below as the authoritative target — do not replicate Django's migration path.

## MySQL-specific adjustments
- `Application.skills` was a Postgres `ArrayField` — use a MySQL `JSON` column (`json` cast to array in Eloquent), same as the other JSON list fields below.
- All other JSON blob/list fields (notes, timeline, credentials, specializations, etc.) use MySQL's native `JSON` column type.
- Avoid any Postgres-only column types/operators (e.g. `ArrayField`, `JSONField` querysets using `contains`/`__in` on arrays) — implement equivalent filtering with MySQL `JSON_CONTAINS`/`whereJsonContains` where the reference relies on that behavior (e.g. filtering intakes by `services_needed`, team members by `specializations`).
- Unique constraints and enum-like `CharField(choices=...)` fields map to MySQL `enum` columns or `string` + validation — prefer `string` + PHP backed enum + Eloquent cast for portability (MySQL `enum` columns are harder to alter later).

## Key business-rule notes carried over from Django migration history
- `Client.assigned_therapist` and `Intake.assigned_therapist` are **FK (many)**, not OneToOne — a therapist can have many clients/intakes. Do not make these unique.
- `Application.skills` was a Postgres `ArrayField` — use a JSON column.
- Notes/timeline fields (`Client.clinical_notes`, `Client.timeline`, `Intake.notes`, `Intake.timeline`, `Application.notes`, `Invoice.timeline`) are **JSON blob arrays with client-generated IDs** in the reference (no server auto-increment) — replicate as JSON columns for exact parity; do not normalize into child tables in this phase.
- `Intake` is NOT a child of `Client` — `Client.original_intake` is a OneToOne pointing back to the `Intake` it was promoted from. Most child/parent/address data lives only on `Intake`.

## Migrations to create (one per model, in dependency order)

### Users & accounts
- `users`: email (unique), password, first_name, last_name, phone, role (enum: admin, therapist, client, staff, guest; default client), is_active, notification prefs (new_intake, invoice_payments, session_reminders, new_applications — all bool default true), timestamps.
- `team_members`: user_id (FK unique), position, resident_status, department (enum), employment_status (enum), hire_date, hourly_rate (decimal 8,2), maximum_caseload (uint default 0), credentials (json list), specializations (json list), emergency_contact_name/phone, can_access_finance/can_manage_team/can_manage_clients (bool), additional_notes, title, description, photo, client (json list of client ids), application_id (FK nullable → applications), phone, office_phone, street_address, address_line_2, city, province, zip_code, availability (json list), documents (json list), secondary_email, birthdate, required_documents (json list), sin_number (store hashed, same as reference), license_number, years_of_experience, timestamps.

### Core domain
- `services` (public marketing services): name, code (unique slug), short_description, description, duration_minutes (default 60), base_price (decimal 8,2), is_active, benefits/offerings/approaches/outcomes/area_of_focus (json lists), description_highlight/tags (json), ages, signs_to_look_for, conditions, frequency, location, main_tag, duration, photo, timestamps.
- `service_offerings` (internal billable offerings): name, code (unique slug), description, is_active, type (enum general_service/specific_service/non_direct_service), base_price (decimal 10,2), metadata (json), timestamps.
- `contacts`: contact (json list), timestamps.
- `intakes`: submitted_by_id (FK users, nullable), completed (bool), child fields (first/middle/last name, dob, age, gender enum), status (string default pending), address fields, grade_level, school_name, services_needed (json), currently_receiving_services (bool), diagnosis (json), has_medical_conditions (bool), languages_spoken_at_home, require_interpreter (bool), funding_source, available_days/preferred_times (json), primary_parent_* and secondary_parent_* fields, additional_information, reviewed (bool), approved_as_client (bool), linked_client_id (uuid nullable — legacy), medical_conditions, interpreter_needed, receiving_services_desc, admin_addition_informations, theraphy_goals, referral_source, funding_number, annual_funding, notes (json), timeline (json), emergency_contact_*, funding_source_info (json), assigned_therapist_id (FK users nullable), assigned_at (date), reference_number (unique, format INT-{year}-{seq}), consents (json list of acceptance ids), timestamps.
- `intake_documents`: intake_id (FK cascade), name, type, file (nullable path), drive_file_id/drive_file_url/drive_web_view, uploaded_at.
- `clients`: original_intake_id (FK intakes, unique, nullable), primary_therapist_id (FK users nullable), user_id (FK users, unique, nullable — parent login), assigned_therapist_id (FK users nullable), assigned_at (date), approved_date (datetime), clinical_notes (json), active_services (text), allergies (json), contract_start_date/end_date/signed_date (date), timeline (json), status (enum active/paused/completed/inactive/archive), consents (json), service_availed (json — denormalized cache), timestamps.
- `client_user` (or `care_team` pivot): client_id, user_id — many-to-many care team.
- `client_services`: client_id (FK cascade), service_id (FK service_offerings nullable), therapist_id (FK users nullable), frequency, duration, start_date, funding_source, no_sessions (uint default 0), goals (text), timestamps.
- `schedule_sessions`: client_id (FK nullable), therapist_id (FK users nullable), service_id (FK service_offerings nullable), service_name (nullable string fallback), linked_client_service_id (FK client_services nullable), scheduled_start/scheduled_end (datetime), location, duration (string), notes, status (enum: scheduled/completed/cancelled/inprogress/confirmed/no_show/pending/disputed), elapsed_time, start_time/end_time (actual), cancel_reason, dispute_reason, timestamps.
- `billing_accounts`: client_id (FK unique nullable), currency (default CAD), balance (decimal 12,2), timestamps.
- `invoices`: client_id (FK nullable), therapist_id (FK users nullable), billing_account_id (FK nullable, legacy), session_id (FK schedule_sessions nullable), reference (unique), invoice_id (unique, format INV-{8hex}), services (json list of line items), sub_total/tax_percentage(default 5.00)/gst/total/amount_due (decimals), invoice_date/due_date (date), paid_at (datetime)/paid_date (date legacy), status (enum sent/draft/paid/overdue/unpaid/refunded), processed_by, issued_by_id (FK users nullable), notes, timeline (json), bill_to_name/email/phone/address, billed_by (enum therapist/admin), linked_therapist_invoice_id (self FK nullable), timestamps.
- `client_documents`: intake_id (FK nullable), client_id (FK nullable), user_id (FK nullable), doc_type (freeform text), title, upload_origin (enum admin/therapist/client), drive_file_id/drive_file_url/drive_web_view, notes, uploaded_by_id (FK users nullable), uploaded_at.
- `applications` (hiring): personal fields, address fields, position_applied, position_id, profession_status, preferred_start_date, is_working_with_other (bool), resume/cover_letter (urls), drivers_license/has_vehicle (bool), lead_source, reason_for_applying, other_notes, application_status (enum pending/reviewing/interview_scheduled/shortlisted/hired/declined), internal_notes/notes (json), experience, expected_salary, notice_availability, hourly_rate, hire_date, interview_date/interview_time, interview_platform, education, skills (json — was Postgres array), candidate_rating (uint nullable), hired/declined (bool), availability/references (json), resident_status, reference_number (unique, format APP-{year}-{seq}), timestamps.
- `messages`: sender_id/recipient_id (FK users), subject, body, is_read (bool), sent_at, timestamps.
- `system_logs`: user_id (FK users nullable), action, details (json), ip_address, timestamps.
- `complaints`: client_id (FK cascade), subject, description, status (enum resolved/open/under_review), type (enum complaints/disputes), admin_response, category, resolve_at, therapist_id (FK users nullable), session_id (FK schedule_sessions nullable), complained_by (enum client/therapist), consent_given (bool), consent_info, consent_at, ip_address, file (nullable path), drive_file_id/drive_file_url/drive_web_view, reviewed_at, reviewed_by_id (FK users nullable), resolved_by_id (FK users nullable, legacy), timestamps.
- `careers`: position, location, schedule, contract, rate, short_description, about_description, responsibilities/qualifications/skills/benefits (json), is_active (bool), due_date, highlights (json), level, hours, required_documents (json), timestamps.
- `intake_therapist_approvals`: intake_id (FK unique), therapist_id (FK users), status (enum pending/approved/rejected/reassign), notes, decided_at, timestamps.
- `intake_therapist_approval_histories`: intake_id (FK cascade), therapist_id (FK users nullable), status, notes, decided_at, timestamps.
- `consent_documents`: title, is_active (bool), purpose (enum intake/application), version, effective_date, timestamps.
- `consent_clauses`: document_id (FK cascade), order (uint), text_template, unique(document_id, order), timestamps.
- `user_consent_acceptances`: document_id (FK cascade), user_id (FK cascade), accepted_at, revoked_at, is_revoked (bool), unique(document_id, user_id), timestamps.

## Eloquent models
- One model per table above with `$fillable`/casts (json array casts for all json columns, enum casts where applicable), relationships (belongsTo/hasMany/belongsToMany/hasOne matching FK notes), and the reference's computed properties/methods ported as accessors or model methods:
  - `User`: `full_name` accessor, `isAdmin()`, `isTherapist()`, `isClient()`.
  - `Client`: `assignTherapist()`, `reassignPrimaryTherapist()` (writes timeline entry, updates care team).
  - `ConsentDocument`: `isCurrent` accessor (`is_active && effective_date <= today`).
  - `Invoice`: `calculateTotals()` method recomputing sub_total/gst/total from `services` json.

## Seeders/factories
- Factory per model for test coverage (Phase 16 tests depend on these).
- Seed the same reference-number generation logic (`INT-{year}-{seq}`, `APP-{year}-{seq}`) as a small service/helper — use a DB-safe increment (e.g. `lockForUpdate` or a dedicated counter table) to avoid the race condition noted in the Django reference.

## Acceptance criteria
- `php artisan migrate:fresh` runs clean.
- Every model has PHPDoc array-shape docblocks for JSON-cast fields per project PHP conventions.
- `larastan`/`phpstan` passes on all new models.
