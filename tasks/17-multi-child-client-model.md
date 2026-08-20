# Phase 17 — Multi-Child Client Model

> **Status: implemented.** Migration `2026_08_07_131645_drop_unique_user_id_from_clients_table`
> plus the work items below. 215 tests passing. Two extra findings surfaced during the work and
> were fixed in the same pass — see "Additional findings".

## Goal

Allow one parent to have multiple children in the system, each with their own client
record, sessions, services, and invoices. Today a second intake from the same parent
email is silently swallowed into the first child's record.

---

## Problem

### Root cause

`clients.user_id` carries a unique index:

```php
// database/migrations/2026_07_29_000819_create_clients_table.php:15
$table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
```

The database physically cannot hold two clients for one parent. `IntakeApprovalService::promote()`
works around this at `:80-89` by reusing the existing client rather than creating a new one.

The modeling error: **`Client` is keyed to the parent, but semantically represents the child.**
`original_intake_id` is what actually carries child identity.

### Observed failure

A parent submits a second intake for their second child:

1. The public form accepts it — `CheckEmailController` warns but does not block (`:27-29`)
2. The intake is created correctly with child #2's details
3. Admin review, therapist assignment, and approval all work
4. **At promotion it collapses** — `promote()` finds the parent's existing `Client` and reuses it

Consequences:

- Child #2 gets no client record; intake #2's `linked_client_id` points at child #1's client
- `original_intake_id` is set once at creation (`IntakeApprovalService.php:92`) and never repointed,
  so the client permanently displays child #1
- `client_services` **does** accumulate — `recordClientService()` writes against the shared client,
  so child #2's approved service lands on child #1's record

What a therapist sees on the client card:

| Card element | Source | Whose data |
|---|---|---|
| Name, age, diagnosis | `original_intake` | Child #1 only |
| Parent phone/email/address | `original_intake` | Shared (correct) |
| Service badges | `client_services` | Both children, merged |
| Availability modal | `original_intake` | Child #1 only |

The session create dropdown (`SessionController::clientOptions()`) labels each client from
`originalIntake` — so a two-child family appears as **one entry under child #1's name**, and
every session booked for that family lands on child #1's record.

---

## Decision: `Client` = child

```
User (parent)  ──has many──▶  Client (child)  ──has one──▶  Intake
```

The parent `User` already serves as the family/household entity — it holds the login and email.
`Client` should mean "one child in care."

### Rejected alternative: `Client` = family, many intakes

Considered and rejected. Every clinical table keys on `client_id`:

```
schedule_sessions.client_id
client_services.client_id
invoices.client_id
billing_accounts.client_id  (unique)
```

If `Client` became the family, `client_id` would no longer identify which child a session,
service, or invoice belongs to. It would require adding `intake_id` to `schedule_sessions`,
`client_services`, and `invoices`, rethinking the `client_user` care-team pivot, and adding a
child selector to every form and query.

| | Client = family | Client = child |
|---|---|---|
| Migration | `intake_id` on 3+ tables | drop one unique index |
| Clinical queries | rewrite broadly | unchanged |
| Session create form | needs child selector | works as-is |
| Therapist card | needs child selector | works as-is |
| `clientProfile` call sites | 12 to update | 12 to update |
| Family-level billing | natural | needs `billing_accounts` → `user_id` |

Both options require the same client-portal work. Everything else favors client-per-child.

---

## Work items

### 1. Migration — drop the unique constraint

New migration:

- Drop the unique index on `clients.user_id`, keep the foreign key
- `clients.original_intake_id` is **already unique** (same migration, `:13`) — no change needed
- Verify no code depends on `user_id` uniqueness for lookups

### 2. `IntakeApprovalService::promote()`

- Keep the existing `User` lookup by parent email — the parent login is shared across children
- Keep the "new account created" guard so a returning parent gets **no** duplicate welcome email
  (`rawPassword` stays `null` → the `Mail::to(...)` at `:124-130` is skipped)
- **Remove the `$existingClient` reuse branch (`:80-89`)** — always create a new `Client` +
  `BillingAccount` per intake
- `original_intake_id` continues to be set at creation and is now always correct

### 3. `User::clientProfile()` → `clientProfiles()`

`HasOne` becomes `HasMany`. **12 call sites across 8 files** assume a single client:

| File | Lines |
|---|---|
| `app/Models/User.php` | 85 |
| `app/Http/Middleware/HandleInertiaRequests.php` | 48 |
| `app/Http/Controllers/SessionController.php` | 360, 379 |
| `app/Http/Controllers/ClientProfileController.php` | 22, 34 |
| `app/Http/Controllers/ClientDashboardController.php` | 19 |
| `app/Http/Controllers/InvoiceController.php` | 319, 334 |
| `app/Http/Controllers/ComplaintController.php` | 79, 111, 188 |

`HandleInertiaRequests:48` currently shares a single global `client_id` — this becomes a list of
the parent's children plus the currently selected one.

### 4. Client portal — child selection

**Decided:** child switcher. A selector in the client layout picks one child; every page scopes
to that child, so each request resolves exactly one `client_id`.

- Add a child selector to the client layout
- Scope `client/calendar`, `client/invoices`, `client/complaints` to the selected child
- `ClientProfileController` currently resolves `$request->user()->clientProfile` and edits the
  linked intake — needs to target the selected child's intake

### 5. Public intake form — block duplicate parent email

Replace the warn-only behaviour with a hard stop and a login path.

1. Parent enters an email that already has a client account
2. Block submission: *"You already have an account. Please log in to register a new intake."*
3. New authenticated route `POST /client/intake` — form pre-fills parent name, address, and
   emergency contact from their existing record; parent supplies only the new child's details
4. Set `submitted_by_id` on the new intake (field already exists on `Intake`, currently unused
   for this)
5. On approval, `promote()` creates a **new** `Client` for that child under the same `User`

Two cautions:

- **Enumeration** — a public endpoint that confirms "this email is registered" leaks the client
  list. Either accept the submission silently and email the "log in to continue" message, or put
  the check behind a rate limit.
- **Check `User`/`Client`, not `Intake`** — `CheckEmailController` currently also matches
  `Intake.primary_parent_email` (`:29`), which would block a parent whose first intake is still
  pending and who has no account yet. That's a dead end for them.

### 6. Billing decision

**Decided:** billing stays per-child. `billing_accounts.client_id` is unique, so each child gets
their own billing account and invoices — this falls out of the client-per-child model with no
extra schema change, and matches per-child funding (FSCD is allocated per child). Family-level
consolidation was considered and deferred.

---

## Related bugs found during analysis

Independent of the multi-child work, worth fixing in the same pass.

### A. Therapist session form is not scoped to caseload

`SessionController::clientOptions()` (`:444`) has no `where` clause — no `primary_therapist_id`,
no care-team check. Compare `TherapistClientController::index`, which scopes carefully.

A therapist opening "New Session" gets a dropdown of **every client in the system**, with child
names and enrolled services. `store()` (`:102`) only forces `therapist_id` to the acting user and
never validates that the chosen `client_id` is theirs, so it can also be bypassed by posting a
different `client_id`.

Fix: scope `clientOptions()` by therapist when `! isAdmin()`, and add a matching server-side
check in `store()` and `update()`.

### B. `ClientCard` fails silently

`resources/js/components/therapist/client-card.tsx:20-22` returns `null` when `original_intake`
is missing. A client whose intake was deleted vanishes from the therapist list with no
indication — the page just shows fewer cards, or "No clients found."

Fix: render a fallback card, or exclude such clients server-side so the count stays honest.

### C. Therapist search matches a field it never displays

`TherapistClientController::index` searches `primary_parent_name` alongside the child name
fields, but the parent's *name* appears nowhere on the resulting card — only their phone and
email. Low priority; either surface the parent name on the card or drop it from the search.

### D. Client record with no parent email is unreachable

`clients.user_id` is nullable, so an intake with no `primary_parent_email` promotes to a `Client`
with `user_id => null`. The record exists but nobody can ever log in to it. Consider requiring a
parent email before promotion, or flagging these in the admin list.

---

## Additional findings (fixed during implementation)

### E. Complaints could be filed against any session

`StoreComplaintRequest` validated `session_id` with `exists:schedule_sessions,id` and nothing
more, so a client or therapist could file a complaint against a session they had no part in.
`ComplaintController::store()` now asserts ownership for both roles before creating the row, and
derives `client_id` from the session rather than from the acting user — which is also what makes
it correct for a parent with several children.

### F. `check-email` was an unauthenticated client-list oracle

The endpoint confirms whether an address is registered and had no rate limit. Now throttled at
`10,1`. It also no longer matches on `Intake.primary_parent_email` (see item 5's second caution).

---

## Suggested order

1. Settle the two open questions — combined vs. per-child portal view, and family vs. per-child billing
2. Related bugs A–B (independent, no schema impact — safe to ship first)
3. Item 1 migration + item 2 `promote()` rewrite
4. Item 3 `clientProfiles` refactor
5. Item 4 client portal child selection
6. Item 5 intake form gating + authenticated intake route

---

## Acceptance criteria

- A parent with two approved intakes has **two** `Client` rows, each with its own
  `original_intake_id`, care team, services, sessions, and invoices.
- Approving a second child's intake creates a new client and does **not** send a second welcome
  email — the parent keeps one login.
- The therapist client list shows each child as a separate card with that child's own name, age,
  diagnosis, services, and availability.
- The session create dropdown lists each child separately, and a therapist sees only clients on
  their own caseload.
- Posting a `client_id` outside the acting therapist's caseload to `sessions.store` or
  `sessions.update` is rejected server-side.
- A parent whose email already has an account cannot submit the public intake form, and can
  register an additional child from inside their authenticated portal.
- Existing single-child clients are unaffected — no data migration required for them.
