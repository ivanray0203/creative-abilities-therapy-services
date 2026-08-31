# Phase 19 — Aide Timesheets

> **Status: planned.** Nothing implemented yet.

## Goal

Give aide positions a record of work that matches the form they actually file. An aide
does not bill the clinic line-by-line the way a therapist does — their record is FSCD's
**TIME SHEET**: a grid of dates against four hour columns, totalled, and signed by both
the aide and the parent.

Today an aide sees Billing and Invoices in their sidebar, neither of which fits. This
adds a parallel pipeline of the same shape — loose lines accumulate, then a generated
document claims them — but recording **hours only**: no rates, no amounts, no invoice.

| | therapist | aide |
| --- | --- | --- |
| loose lines | Billing | **Hours** |
| generated document | Invoices | **Timesheets** |

Like Phase 17 and 18, this is not part of the parity port — the reference had nothing
like it.

---

## 1. Who counts as an aide

`team_members.position` already holds *Behavioural & Developmental Aide* and
*Behaviour Aide*, alongside seven non-aide positions.

### Approach

`TeamMember::isAide(): bool` → `str_ends_with(strtolower(trim($this->position ?? '')), 'aide')`,
with `User::isAide()` delegating through the existing `teamMember` relation.

Matching on the suffix rather than an exact string means a future aide title needs no
code change, and it does not catch `Behavioural Consultant/Therapist (BC)`.

### Watch for

- `position` is nullable. A therapist with no team-member row is **not** an aide, and
  must keep the Billing/Invoices menus.
- `auth.team_member` is already shared on every Inertia response
  (`app/Http/Middleware/HandleInertiaRequests.php`), so the frontend branches on
  `position` with no new prop and no extra query.

---

## 2. Data model

Two tables, following the `billing_items` → `invoices` relationship exactly: loose rows
are claimed by the generated document through a nullable FK, and the document keeps a
frozen snapshot of what it claimed.

### `timesheet_entries` — one row per aide, per client, per date

| column | type |
| --- | --- |
| `therapist_id`, `client_id` | FK |
| `entry_date` | date |
| `hourly_respite_hours`, `community_support_hours`, `bda_direct_hours`, `bda_indirect_hours` | `decimal(6,2)` default `0` |
| `notes` | text nullable |
| `timesheet_id` | nullable FK `timesheets` — null means "not yet on a timesheet" |
| unique | `(therapist_id, client_id, entry_date)` |

### `timesheets` — the generated document

| column | type |
| --- | --- |
| `timesheet_number` | unique string, `TMS-{year}-{seq}` |
| `therapist_id`, `client_id`, `issued_by_id` | FK |
| `period_start`, `period_end` | date |
| `rows` | json — frozen snapshot of the claimed entries |
| `total_hourly_respite`, `total_community_support`, `total_bda_direct`, `total_bda_indirect`, `total_hours` | `decimal(8,2)` |
| `aide_signature`, `parent_signature` | longText nullable (PNG data URI) |
| `aide_signed_at`, `parent_signed_at` | timestamp nullable |
| `status` | `awaiting_client` \| `signed` |
| `not_signed_timesheet`, `signed_timesheet` | string nullable — Drive web-view URLs |
| `timeline` | json — the `{id,title,date,time}` shape used across the app |

### Watch for

- **The snapshot is the point.** A signed form is a record of what the parent agreed to;
  editing an entry afterwards must not rewrite it. `rows` is what the PDF renders from,
  never the live entries.
- The unique key means "log hours for a day I already logged" is an update, not a second
  row. The form must surface that rather than failing on a constraint violation.

---

## 3. Generating and signing

### Approach

`App\Services\TimesheetGenerator`, modelled on `BillingItemInvoiceGenerator`:

```
generate(User $aide, Client $client, CarbonInterface $from, CarbonInterface $to, string $aideSignature): ?Timesheet
```

Inside a transaction: `lockForUpdate()` the aide's unclaimed entries for that client
between the dates, return `null` when there are none, snapshot them into `rows`, sum the
four columns, stamp `timesheet_id` on the entries, and store the aide's signature with
`aide_signed_at`. The PDF is filed **outside** the transaction, as the invoice generator
does — a slow Drive call must not roll the timesheet back.

The aide signs at generation, so a timesheet is never unsigned on their side. It reaches
the parent as `awaiting_client`; their signature flips it to `signed`.

`App\Services\TimesheetDocumentService` mirrors `InvoiceDocumentService`:
`storeUnsigned()` / `storeSigned()` into `CATS/Timesheet/not-signed` and
`CATS/Timesheet/signed` through the existing `DriveStorage`, with failures logged and
swallowed so an outage never costs a signature.

### Watch for

- Two generates over overlapping ranges must not claim the same day twice — that is what
  the `lockForUpdate()` + `timesheet_id` stamp is for.
- Generating a range with nothing left in it is a validation error on the date field, not
  an empty document.

---

## 4. The PDF

`PdfService::timesheet(Timesheet $timesheet, ?string $parentSignature = null)` +
`resources/views/pdf/timesheet.blade.php`, built off `resources/views/pdf/invoice.blade.php`
— same letterhead block, same `#D87E45` header bar, same filler-row padding and
signature-box markup.

- Left block — `CLIENT:` → `Client::displayName()`; `DOB:` → `intake.date_of_birth`;
  `FSCD FILE #:` → `intake.funding_number`.
- Right block reads `config('cats.invoice')` — business partner #, legal name, street,
  city line, phone and website are already there.
- Grid: `Date | Hourly Respite | Community Support Aide | BDA Direct Hours |
  BDA Indirect Hours` under a spanning "Types of Service and Hours" header, then a
  TOTAL HOURS row per column and a combined TOTAL HOURS.
- Footer: `AIDE'S SIGNATURE` (aide name) and `PARENT'S SIGNATURE` (parent name).

---

## 5. Authorization and reachability

`App\Policies\TimesheetPolicy`, modelled on `InvoicePolicy`:

- `view` — admin always; aide only their own; client only via `ClientContext::owns()`.
- `sign` — client, owns the client, `parent_signature === null`. A signed form is not
  re-signable.
- `delete` — admin.

`App\Http\Middleware\EnsureAide`, aliased `aide` beside `role` in `bootstrap/app.php`:

- `->middleware('aide')` on the therapist `hours` and `timesheets` routes — a non-aide
  therapist is redirected to `/therapist/billing`.
- `->middleware('aide:never')` on the existing therapist `billing` and `invoices` routes
  — an aide is redirected to `/therapist/hours`.

### Watch for

Hiding a menu item is not access control. Without the `aide:never` half, an aide who
types `/therapist/billing` still reaches a form that would raise money lines against
their name.

---

## 6. Routes and navigation

`timesheets/generate` must be declared **before** `timesheets/{timesheet}`, or the
literal is swallowed by the binding.

| group | routes |
| --- | --- |
| admin | `timesheets` index, show, pdf, destroy |
| therapist (`aide`) | `hours` index/create/store, `hours/{entry}` destroy; `timesheets` index, generate, show, pdf |
| client | `timesheets` index, show, pdf, sign |

Sidebars:

- `therapist-sidebar.tsx` — for an aide, `Billing` → **Hours** and `Invoices` →
  **Timesheets**, in the same positions. Everyone else unchanged.
- `admin-sidebar.tsx` — **Timesheets** immediately below Billing.
- `client-sidebar.tsx` — **Timesheets** after Invoices.

---

## 7. Frontend

Mirrors the existing `billing/` and `invoices/` page pairs, role-aware through the
`usePage()` layout pattern at the bottom of `resources/js/pages/invoices/index.tsx`.

| file | mirrors |
| --- | --- |
| `pages/hours/index.tsx` | `billing/index.tsx` — totals per column, claimed/unclaimed filter |
| `pages/hours/create.tsx` | `billing/create.tsx` |
| `pages/timesheets/index.tsx` | `invoices/index.tsx` — admin defaults to the Signed filter |
| `pages/timesheets/show.tsx` | `invoices/show.tsx` — Print/PDF, and the parent's Sign button |
| `components/hours/hours-form.tsx` | `billing/billing-form.tsx` — repeatable day rows, hours summary in place of the money one |
| `components/timesheets/generate-timesheet-modal.tsx` | `invoices/generate-invoice-modal.tsx` + the existing `signature-pad` |
| `components/timesheets/sign-timesheet-modal.tsx` | `invoices/sign-invoice-modal.tsx` |

`resources/js/types/timesheet.ts` follows `types/invoice.ts`.

### Watch for

The hours form has no rates and no totals in dollars. Reusing `billing-form.tsx`
wholesale would drag the rate-card and funding-stream logic along with it; only the
client picker and the add/remove-line affordances are worth carrying over.

---

## Verification

1. `php artisan migrate`, then `npm run build` (or `composer run dev`).
2. `tests/Feature/TimesheetControllerTest.php` (Pest):
   - an aide saves hour entries; a non-aide therapist hitting `/therapist/hours` is
     redirected to billing, and an aide hitting `/therapist/billing` is redirected to
     hours;
   - `generate` claims exactly the unclaimed entries in range, totals the four columns,
     stores the aide signature, and mails the parent (`Mail::fake()`);
   - generating the same range twice returns a validation error the second time;
   - the parent signs → `signed`, `parent_signed_at` set, admins notified; a second sign
     attempt is refused by the policy;
   - a parent from another family gets 404 on `show`/`sign` (mirroring
     `CrossTenantAccessTest`);
   - the admin index lists the signed timesheet;
   - `PdfService::timesheet()` returns `%PDF` bytes.
3. `php artisan test --compact --filter=Timesheet`, then
   `php artisan test --compact --filter="Invoice|Billing"` to confirm the existing
   pipeline is unaffected.
4. `vendor/bin/pint --dirty --format agent` and `npm run lint`.
5. Manual pass: log in as an aide → Hours and Timesheets present, Billing and Invoices
   gone → log hours → generate with a signature → log in as that child's parent →
   Timesheets → sign → log in as admin → Timesheets → open the signed PDF and check it
   against the reference form.
