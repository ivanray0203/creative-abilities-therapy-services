# Phase 18 — Scheduling Conflicts, Review Queue & Authorization

> **Status: items 1–3 and the first two of item 4 implemented.** 242 tests passing.
> Decisions taken during the work are recorded inline below. The remaining item 4
> entries (modal audit, stub pages) need a product call and are untouched.

## Goal

Close three gaps that let the app do the wrong thing quietly: a therapist can be
booked twice at the same time, an assigned intake review can become unreachable, and
several destructive actions are protected by nothing but how their routes happen to be
grouped.

Like Phase 17, this is not part of the parity port — it changes behaviour the reference
had.

---

## 1. Scheduling conflict prevention

### Problem

There is **no overlap validation anywhere** — not in `SessionController`, not in
`StoreSessionRequest`. Nothing prevents:

- booking the same therapist for two sessions at the same time
- booking the same child into two sessions at the same time
- an admin reassigning a session onto a therapist who is already busy

For a scheduling application this is the most consequential missing rule: the failure is
silent, and it surfaces as two families arriving for the same slot.

### Approach

Add a validation rule to `StoreSessionRequest` (inherited by `UpdateSessionRequest`)
that rejects a session overlapping an existing one for either participant.

- Compare against `scheduled_start` / `scheduled_end`, which
  `SessionController::scheduleAttributes()` already computes.
- Overlap test is `existing.start < new.end AND existing.end > new.start` — touching
  boundaries (back-to-back appointments) must stay legal.
- **Exclude the session being edited**, or every update would collide with itself.
  `UpdateSessionRequest` needs the bound session's id via `$this->route('session')`.
- **Exclude `cancelled` and `no_show`** from conflict checks — those slots are free
  again. The full status set is `scheduled, completed, cancelled, inprogress,
  confirmed, no_show, pending, disputed`.
- Report therapist conflicts and client conflicts as distinct messages, naming the
  clashing time, so the error is actionable.

### Watch for

- The therapist path forces `therapist_id` to the acting user in the controller, not
  the request, so the rule must resolve the effective therapist the same way
  (`isAdmin() ? input : user()->id`) or admins and therapists will be checked against
  different people.
- A same-therapist, same-client double booking should report once, not twice.

---

## 2. Therapist review queue

### Problem

There is no therapist-facing intake **index** — only
`GET /therapist/intake/{intake}` (`TherapistIntakeController::show`). The sole way a
therapist discovers assigned reviews is the `pendingReviews` block on their dashboard,
and that block applies a subtle extra filter:

```php
// TherapistDashboardController
->filter(fn (Intake $intake): bool => $intake->therapistReviews->contains(
    fn (IntakeTherapistApproval $review): bool =>
        in_array($review->status, ['pending', 'reassign'], true)
        && $review->latestHistoryStatus() === 'sent',
))
```

A review whose latest history row isn't `sent` drops off the dashboard — and with no
index route, becomes **unreachable through the UI entirely**. The therapist can't act
on it, and the intake stalls with no visible owner.

### Approach

- Add `TherapistIntakeController::index` and `GET /therapist/intake`, named
  `therapist.intake.index`.
- Scope to intakes with a review row for the acting therapist. Deliberately **do not**
  reuse the dashboard's `latestHistoryStatus() === 'sent'` filter — the index is the
  safety net, so it should show everything assigned to them.
- Filter tabs for pending / decided, plus search on child name and reference number,
  matching `TherapistClientController::index` and the client intake list.
- Add "Reviews" to the therapist sidebar beside "My Clients".
- Reuse the shared intake status badge (see item 4 below).

### Decided

The `sent` filter is defensive rather than meaningful: `assignTherapistForReview` always
writes a `sent` history row immediately after creating or updating the review, so in the
normal flow it agrees with `review.status`. The index therefore scopes on assignment
alone — a strict superset — and the dashboard was left as it was. A test asserts the
index still lists an assignment with no history rows at all, which the dashboard would
drop.

---

## 3. Authorization consolidation

### Problem

`app/Policies` is empty and no `Gate::define` calls exist anywhere. Every check is
ad-hoc inside controllers. Phase 17 already surfaced two real holes:

- complaints could be filed against **any** session, by either role
- the therapist session form listed **every client in the system** and accepted any
  posted `client_id`

Both are fixed, but they were found by accident, not by design. Worse, four methods
carry **no in-method check at all** and are safe only because of route grouping:

| Controller | Methods | Protected by |
|---|---|---|
| `InvoiceController` | `destroy`, `markPaid` | admin route group only |
| `ComplaintController` | `startReview`, `resolve` | admin route group only |

Adding one route that points at these from a therapist or client group silently exposes
deleting invoices and resolving complaints.

### Approach

- Introduce `InvoicePolicy`, `ComplaintPolicy`, `SessionPolicy`, `ClientPolicy`,
  `IntakePolicy` and move the existing private `assertOwnsOrAdmin` /
  `assertCanView` / `assertParticipant` / `assertOwnsAsClient` helpers into them.
- Give the four unguarded methods explicit authorization, so route grouping becomes
  defence in depth rather than the only defence.
- Keep `ClientContext::owns()` as the client-side ownership source of truth — the
  parent/child distinction it encodes (authorization spans all children, list scoping
  follows the switcher) should live in one place.
- Add a **role access matrix test**: for each destructive route, assert every role that
  shouldn't reach it gets 403/404. `tests/Feature/RoleAccessTest.php` already exists
  and is the natural home.

### Watch for

Policies auto-resolve on route-model-bound parameters; the shared controllers are
mounted under three different route groups, so verify each still behaves per-role
rather than assuming the admin path.

### As implemented

- Three policies, not five: `InvoicePolicy`, `ComplaintPolicy`, `ScheduleSessionPolicy`.
  Client and Intake access is already explicit (`abort_unless` in
  `TherapistIntakeController`, admin-only route groups), so empty policies for them
  would have been noise. Worth revisiting if those surfaces grow.
- **Denials stay 404, not 403.** `$this->authorize()` raises 403, which would tell a
  stranger the record exists — the app deliberately doesn't. Controllers call
  `abort_unless($user->can(...), 404)` instead, and the existing private assert helpers
  were kept as thin wrappers so call sites and tests were unaffected.
- `AuthorizesRequests` was added to the base `Controller`, which had no traits at all.
- `SessionController` no longer needs `ClientContext` — the ownership logic moved into
  the policy, and PHPStan caught the now-dead property.
- The matrix test registers throwaway routes outside the admin group to prove the guard
  holds when routing doesn't. Verified it discriminates: removing the invoice `delete`
  guard turns the expected 404 into a 302 and the invoice is actually deleted.

---

## 4. Smaller items worth folding in

- ~~**Unify intake status badges.**~~ **Done.** `components/intake/status-badge.tsx`
  holds the five statuses; the admin pipeline spreads that map and adds only its two
  therapist-review variants.
- ~~**`schedule_sessions.duration` is a string.**~~ **Done.** Now
  `unsignedSmallInteger` minutes; `durationMinutes()` is gone and validation is
  `integer|min:5|max:480`. Note the value conversion runs in PHP, not SQL: the suite
  runs on **SQLite in-memory** while the app runs on MySQL, and a first attempt using
  `SUBSTRING_INDEX`/`REGEXP` broke every test. MySQL strict mode also rejects
  `CAST('60 minutes' AS UNSIGNED)` outright.
- **Audit remaining modals.** The scroll bug fixed in the shared `DialogContent` was
  latent in every dialog; open the taller ones (add-client-service, approve-intake) at
  laptop height to confirm.
- ~~**Resolve the stub pages.**~~ **Done — removed.** `client/dashboard`,
  `client/reports` and `therapist/reports` rendered nothing but "Coming Soon", ported
  from the reference (whose `ReportsPage.tsx` never set `isReady`). Investigation showed
  **no sidebar linked to any of them** — they were orphaned routes reachable only by
  typing the URL, so there was no nav entry to hide. Routes and components deleted and
  Wayfinder regenerated. Easy to restore from git when Reports is actually built; decide
  then what a parent or therapist should see (attendance, funding used, invoice history).

---

## Backlog (not scoped here)

Larger features discussed but deliberately left out of this phase:

- **Session/progress notes** — structured clinical documentation per session.
  `Client.clinical_notes` exists but isn't surfaced; likely a records requirement.
- **FSCD funding consumption** — `funding_number` and `annual_funding` are captured but
  nothing tracks spend against the allocation. Now that billing is per-child
  (Phase 17), used/remaining is straightforward and genuinely useful.
- **Recurring sessions** — therapy is weekly; booking one at a time is real friction.
- **Therapist availability and caseload caps** — the intake review screen already shows
  "schedule match" and "capacity" but nothing enforces either.
- **Therapist client detail page** — currently one card plus an availability modal.
- **Online payment** — invoices are emailed and marked paid by hand.
- **Split `intake-application-form.tsx`** — ~2,600 lines in one file.

---

## Suggested order

1. Item 1 (conflicts) — self-contained, highest consequence
2. Item 2 (review queue), settling the `sent`-filter question first
3. Item 3 (authorization), starting with the four unguarded methods and the matrix test
4. Item 4 cleanups alongside whichever of the above touches that area

---

## Acceptance criteria

- A session overlapping an existing one for the same therapist is rejected with an
  error naming the conflict; back-to-back sessions remain valid.
- The same applies to the same client, reported separately from therapist conflicts.
- Editing a session without changing its time does not conflict with itself.
- Cancelled and no-show sessions do not block their old slot.
- A therapist can reach every intake assigned to them from `/therapist/intake`,
  including ones the dashboard's `sent` filter hides.
- `InvoiceController::destroy`/`markPaid` and `ComplaintController::startReview`/
  `resolve` reject non-admins even when reached from a non-admin route.
- A role access matrix test covers every destructive route against every role.
- Intake status colours come from a single shared component.
