# Phase 20 — Service Contracts & Hour Budgets

> **Status: implemented.** 581 tests passing, Pint clean, static analysis clean of
> new findings, TypeScript and the Vite build green. The three migrations have been
> run on the development database. Decisions taken during the work are recorded
> inline below; the open questions at the foot are still open.

## Goal

A therapist may not schedule against an availed service on nothing but an assignment.
Admin has to authorize the work first, with a **contract**: a pool of hours over a fixed
period, issued against **one** availed service.

A child with two availed services and a contract on only one of them can be booked for
that one and nothing else. Sessions draw hours from the pool. When the pool empties, or
the period ends, scheduling against that service stops — whichever comes first.

Like Phases 17–19, this is not part of the parity port. The reference had no such rule.

### Decisions already taken

| question | answer |
| --- | --- |
| when hours are drawn | drawn at booking from the session's booked duration, released on cancel/no-show; the clock never adjusts them |
| multi-service sessions | hours split explicitly per linked service, stored on the existing pivot |
| unused hours at period end | expire; admin issues the next contract |
| existing data | a migration backfills one contract per existing availed service |

---

## 1. What contracts replace

### Problem

`ClientService::awaitingSchedule()` (`app/Models/ClientService.php`) currently defines
"bookable" as *no live session exists against this availed service*. It allows **at most
one** session per availed service, ever. That is the rule contracts overturn: a 40-hour
contract is worth many sessions.

The scope has five call sites, and every one of them has to change:

| where | what it drives |
| --- | --- |
| `SessionController::clientOptions()` | which clients a therapist sees in the session form |
| `SessionController::selectableClientServices()` | which availed services the form offers |
| `StoreSessionRequest::failOnUnbookableServices()` | server-side rejection of a posted id |
| `StoreSessionRequest::clientOnOwnCaseload()` | "no remaining service of yours left to schedule" |
| `TherapistClientController::index()` | the `has_bookable_service` flag behind "Create Session" |

Plus the frontend string in `resources/js/components/sessions/sessions-form.tsx`:
*"No availed services left to schedule"*.

### Approach

Replace the scope rather than adding beside it:

```php
#[Scope]
protected function bookableOn(Builder $query, CarbonInterface $date): void
```

→ *has a contract whose period covers `$date` and whose remaining balance is above zero*.

### As implemented

Two scopes on `ServiceContract` rather than one, because the pickers and the ledger
want different questions answered:

- `coveringOn($date)` — not cancelled, and the period covers the day. This is the
  contract a session *belongs to*.
- `openOn($date)` — `coveringOn`, plus a balance above zero. This is what
  `ClientService::bookableOn()` filters on.

The split matters on an edit. A session that has spent its contract dry still has to be
movable, and the therapist should read "0 of 40 hours left" rather than "there is no
contract" — so `ClientService::contractOn()` resolves through `coveringOn`, and the
hours are checked separately with the session's own draw excluded.

`bookableOn` is a `whereIn` against a contract subquery rather than a `whereHas`
closure: the closure form left the builder's generic unresolved for static analysis at
every call site.

### Watch for

- **Delete `awaitingSchedule`, do not leave it in place.** If both scopes survive, an
  availed service with 39 of 40 hours left still disappears from the picker the moment
  its first session is booked, and the contract does nothing.
- The scope now needs a **date**. Bookability is not a property of the service alone —
  the same service is bookable in August and not in September. Every call site has to
  decide which date it means: the form pickers use today, the request validator uses the
  session's own date.

---

## 2. Data model

### `service_contracts`

| column | type |
| --- | --- |
| `contract_number` | unique string, `CON-{year}-{seq}` |
| `client_service_id` | FK `client_services`, cascadeOnDelete |
| `therapist_id` | FK `users` nullOnDelete — snapshot of who the contract authorizes |
| `issued_by_id` | FK `users` nullOnDelete |
| `allotted_hours` | `decimal(8,2)` |
| `period_start`, `period_end` | date |
| `status` | `active` \| `exhausted` \| `expired` \| `cancelled`, default `active` |
| `notes` | text nullable |
| index | `(client_service_id, status)`, `(period_start, period_end)` |

`contract_number` follows the existing `ReferenceNumberGenerator` pattern — add a
`contract()` method beside `timesheet()` and `expense()`, which already share the
locking `next()` helper.

There is deliberately **no** unique constraint on `(client_service_id, period)`.
Overlapping periods are rejected in validation with a message naming the clash, not by a
duplicate-key 500.

### `client_service_schedule_session` gains two columns

The pivot created in `2026_08_10_011526_create_client_service_schedule_session_table.php`
becomes the hours ledger:

| new column | type |
| --- | --- |
| `hours` | `decimal(6,2)` not null default `0` |
| `service_contract_id` | nullable FK `service_contracts`, nullOnDelete |

One row per `(session, availed service)` — the unique index
`session_client_service_unique` already guarantees that — carrying how many hours that
session drew and **which contract it drew from**.

**As implemented**, the pivot got a class of its own, `SessionServiceAllocation`, wired
in with `->using()` on all three relations. Two extra columns implied at four call sites
were worth declaring once, and it puts a `decimal:2` cast on `hours`.

Freezing the contract id matters. Without it, a session booked in August would silently
re-attach to September's contract the moment the balance is recomputed, and last month's
history would rewrite itself.

### Why the pivot rather than a new ledger table

The link already exists, is already unique per pair, and is already loaded everywhere
sessions are rendered (`with('clientServices.service')` appears in four controllers). Two
columns on it means one row per draw and nothing to reconcile between two tables.

### Balance is derived, never stored

```
ServiceContract::usedHours()      // sum of pivot.hours for sessions not cancelled/no_show
ServiceContract::remainingHours() // allotted_hours - usedHours()
```

A cached `used_hours` column would have to be adjusted by every path that touches a
session: store, update, cancel, delete, destroy, end, and admin reassignment. One missed
path sells the same hour twice, silently. Sum on read; hold a row lock for the write
(§4).

---

## 3. Admin — issuing a contract

### Approach

Routes alongside the existing client-service routes in the admin group of
`routes/web.php` (lines 145–147):

```
POST   clients/{client}/services/{clientService}/contracts
PUT    clients/{client}/services/{clientService}/contracts/{contract}
POST   clients/{client}/services/{clientService}/contracts/{contract}/cancel
DELETE clients/{client}/services/{clientService}/contracts/{contract}
```

`Admin\ServiceContractController` + `StoreServiceContractRequest` /
`UpdateServiceContractRequest`. `guardServiceBelongsToClient()` in
`Admin\ClientController` is the shape to copy for the nesting guard — a mismatch is a
404, not a 403, matching the rest of the app.

**As implemented**, `UpdateServiceContractRequest` is an empty subclass. Both rules that
only bite on an edit read the bound contract off the route, so the parent covers issuing
and amending without a second rule set.

Validation:

- `allotted_hours` — numeric, `min:0.25`, `max:2000`
- `period_end` — `after_or_equal:period_start`
- no overlap with another non-cancelled contract on the same availed service
- the availed service must have a `therapist_id`

Every mutation calls `AuditLogger::log()`, as every other write in this app does.

`Client::refreshServiceAvailedCache()` must carry a contract summary into the
`service_availed` JSON cache, so `admin/clients/show.tsx` keeps rendering from a single
source instead of growing a second query.

### Watch for

- **Reducing `allotted_hours` below what is already drawn must fail**, naming the drawn
  total. Otherwise remaining goes negative and the gate reports "exhausted" for hours
  that were genuinely delivered.
- **Deleting a contract with sessions against it must be refused.** Cancelling is the way
  out. `TimesheetEntryController::destroy()` already refuses to delete hours that a
  timesheet has claimed — same message shape, same reasoning.
- A `ClientService`'s therapist can be reassigned by admin, which strands the contract's
  `therapist_id` snapshot. Recommend **blocking reassignment while an active contract
  exists**, with a message telling the admin to cancel the contract first. Silently
  re-pointing it would move authorized hours to someone the funder never approved.

---

## 4. Drawing hours at booking

### Problem

`SessionController::store()` and `update()` currently sync bare ids:

```php
$session->clientServices()->sync($this->linkedClientServiceIds($validated));
```

The pivot now carries hours, so the sync needs a value per id, and that value has to be
checked against a balance before it is written.

### Approach

**Request shape.** `linked_client_service_ids` becomes `linked_client_services`, an array
of `{client_service_id, hours}`. `hours` is optional; omitted, it defaults to the
session's derived duration split evenly across the picked services. Replace the old field
outright and update `sessions-form.tsx` in the same change — leaving both accepted means
two code paths to keep honest.

**Decided during the work:** a partly-filled split is not honoured. If any service was
left without an hours figure, the whole visit is split evenly, because a mix of typed and
inferred shares adds up to a total nobody intended.

**Decided during the work:** `clientOnOwnCaseload` stands down whenever the therapist
named services explicitly. Both rules would otherwise fire and the vaguer message —
"no contracted service available on that date" — would mask the precise one.

**A `ServiceContractLedger` service** in `app/Services/` owns the arithmetic:

| method | job |
| --- | --- |
| `contractFor(ClientService, CarbonInterface): ?ServiceContract` | the active contract whose period covers a date |
| `assertCanDraw(ServiceContract, float $hours, ?int $ignoreSessionId): void` | throws `ValidationException` when the draw exceeds remaining |
| `apply(ScheduleSession, array $allocations): void` | locks, revalidates, syncs the pivot — one `DB::transaction` |

Form-request validation calls the first two so the therapist gets a field error rather
than an exception page. `apply()` repeats the check under a lock, because the first pass
is advisory.

### Watch for

- **Two therapists booking the last hours of one contract at the same time.** The
  authoritative check runs inside `apply()`'s transaction with `lockForUpdate()` on the
  contract rows, taken **in ascending id order** so two multi-service bookings cannot
  deadlock against each other. The loser gets the same `ValidationException` the form
  would have raised, not a 500.
- **An edit must exclude its own current draw.** Moving a 2-hour session ten minutes
  later would otherwise fail against its own hours.
  `StoreSessionRequest::ignoredSessionId()` already exists for exactly this and is
  overridden by `UpdateSessionRequest` — reuse it, do not invent a second mechanism.
- **The session's date must fall inside the contract period.** Rescheduling a session out
  of its contract's window is rejected with a message naming the window. This is a
  different failure from "no hours left" and reads differently to the therapist.
- **The split must total the session length.** A 2-hour visit split 1.5 / 0.25 is either
  a typo or an attempt to under-report; reject it rather than guessing.
- **Cancelling does not delete pivot rows.** The balance already excludes `cancelled` and
  `no_show`, which is what frees the hours, and keeping the rows keeps the history
  readable on `admin/clients/service-sessions.tsx`. This matches how
  `StoreSessionRequest::failOnConflict()` already treats those two statuses.

---

## 5. Completion does not touch the ledger

### Reversed after the first implementation

The original design trued each pivot row's `hours` up to the **actual** elapsed time when
the therapist clocked out: a 2-hour visit split 1.0 / 1.0 that ran 1h50m became
0.92 / 0.92. **That is no longer the rule.** A contract now pays for **booked** time.
`ServiceContractLedger::reconcile()` and `ServiceContract::overDeliveredHours()` are
gone, and `SessionController::endSession()` writes `elapsed_time` and nothing else.

### Why

- **What admin authorized is scheduled time.** The contract is agreed against a plan of
  visits, so the pool has to drain by the same unit it was sized in. Draining by the
  clock means the balance a therapist saw when booking and the balance they get are
  different numbers, for reasons outside their control.
- **A balance that moves after the fact cannot be planned against.** Under
  reconciliation, a 40-hour pool booked to the hour might hold 41 or 38 hours of visits
  depending on how the month ran. Neither the admin issuing the contract nor the funder
  reading it could say in advance how many sessions it bought.
- **It removed the only way to overdraw.** Every draw is now checked against the
  remaining balance before it is written, so `remainingHours()` cannot go negative and
  the sweep's `exhausted` never covers hours nobody authorized.
- Shortening the pool is still possible and still deliberate: an admin edits the
  session's times, which re-runs `apply()` and rewrites the draw.

### Watch for

- **`elapsed_time` is still recorded**, and timesheets still read it. Do not let the two
  ideas merge again — one is what happened, the other is what was authorized.
- **A session ended with no `start_time` keeps its draw**, as it always did. That path
  now needs no special handling at all: nothing on the end route touches hours.

---

## 6. What the therapist sees

### Approach

`resources/js/components/sessions/sessions-form.tsx`:

- each availed-service option shows **remaining hours and period end** inline
- an option with no contract, or an expired or exhausted one, is **absent**, and the
  empty-state text names the reason instead of the current
  *"No availed services left to schedule"*
- per-service hour inputs appear once more than one service is picked, pre-filled with
  the even split, with a running total that must match the session length
- the client dropdown, fed by `SessionController::clientOptions()`, follows the same rule

`TherapistClientController::index()`'s `has_bookable_service` becomes contract-aware, so
the caseload row's "Create Session" button and the form agree.

### Watch for

- **The reason a service is unbookable matters.** "No contract yet", "Contract ended 12
  Aug" and "0 of 40 hours left" are three different problems and only one of them is the
  therapist's to fix. Send the reason down as a prop; filtering silently turns every case
  into a support ticket.

---

## 7. What admin and client see

- **As implemented:** there was no services tab to extend — the availed services are a
  read-only card on Overview. Contracts got a tab of their own on
  `admin/clients/show.tsx`, one block per availed service showing
  allotted / used / remaining / period / status, with issue, edit, cancel and delete.
  A remaining figure below zero is shown in red rather than floored. Nothing should be
  able to produce one now that draws are checked before they are written, so a red
  figure means a bug or a hand-edited row rather than a normal overrun.
- `admin/clients/service-sessions.tsx`: an hours column per session. The page is already
  the per-service session history, so the ledger belongs there and nowhere new.
- Client portal: **open question** (below) — whether a parent sees their remaining hours.

---

## 8. Statuses and expiry

`status` is a cached read of two derived facts, kept for list filtering and display.

A scheduled command flips `active` → `expired` past `period_end`, and `active` →
`exhausted` at zero remaining. Register it in `routes/console.php` beside the existing
`invoices:mark-overdue` daily entry.

### Watch for

- **The booking gate must never trust `status` alone.** A contract expires at midnight
  and the sweep runs later; the gate reads period and balance directly. `status` is for
  humans and for filters.
- `MarkInvoicesOverdueCommandTest` is the pattern to copy, test included.

---

## 9. Backfill

One migration, run after the schema is settled:

- one contract per existing `client_services` row
- `period_start` = `start_date` ?? `created_at` date; `period_end` = `period_start` + 1 year
- `allotted_hours` = hours already drawn, rounded up to the next 10, minimum 40
- `notes` = `'Backfilled at contract rollout'`
- pivot `hours` = the session's `duration` minutes / 60, split evenly across that
  session's linked services

### Watch for

- **Run the arithmetic in PHP, not SQL.** The suite is SQLite in-memory and production is
  MySQL; `2026_08_07_144459_change_duration_to_minutes_on_schedule_sessions_table.php`
  spells out why and shows the `chunk()` shape to copy.
- **An availed service with no therapist and no sessions still gets a contract.**
  Otherwise it vanishes from every picker the moment this deploys.
- The `down()` migration drops the columns and the table. It must **not** try to restore
  `awaitingSchedule` behaviour — that scope is gone by then.

---

## 10. Tests

**As implemented.** New: `ServiceContractTest`, `ServiceContractLedgerTest`,
`ServiceContractSweepTest`, `ServiceContractBackfillTest`. All four are feature tests —
the ledger needs a database, and `tests/Pest.php` only binds `RefreshDatabase` in
`Feature` and `Browser`.

Edited: `SessionControllerTest`, `SessionConflictTest`, `SessionNotificationTest`,
`TherapistCaseloadScopingTest`, `TherapistClientControllerTest`, plus a
`contractedService()` helper in `tests/Pest.php` and an hours-aware
`ScheduleSessionFactory::linkedTo()`.

The backfill is tested by requiring the migration file and calling `up()` against
pre-contract data, since the suite has already run it once against an empty database.

Cases that have to pass:

- no contract → the service is absent from the picker, **and** a hand-posted id is rejected
- contract with hours left → bookable **repeatedly**, which is the direct inverse of
  today's one-session-per-service assertion
- booking to exactly the allotment → allowed; one minute more → rejected, naming remaining
- cancel, no-show and delete each return their hours
- a session dated outside the period → rejected
- a two-service session draws from two contracts independently
- a split that does not total the session length → rejected
- ending short and ending long both leave the draw at the booked duration, and `elapsed_time` is still recorded
- editing a session excludes its own draw from the balance
- an expired contract is unbookable even with hours left
- the sweep flips `active` → `expired` and `active` → `exhausted`
- after the backfill migration, every pre-existing availed service is still bookable

---

## Suggested order

1. Migrations, models, factories, `ReferenceNumberGenerator::contract()`
2. `ServiceContractLedger` + form-request rules — the backend gate, tested headless
3. `SessionController` store/update wiring
4. Retire `awaitingSchedule` across all five call sites
5. Admin contract CRUD + audit logging
6. Frontend: sessions form first, admin services tab second
7. Sweep command + scheduler entry
8. Backfill migration **last**, once the shape has stopped moving

---

## Acceptance criteria

- A therapist cannot create a session against an availed service that has no contract.
- Two availed services, one contract → only the contracted one is selectable, in the UI
  and against a hand-crafted POST.
- A 40-hour contract supports many sessions until the 40 hours are drawn, then refuses
  the next one with a message naming what is left.
- Hours left inside the period stay bookable; the period ending closes the contract even
  with hours remaining.
- Cancelling a session returns its hours to the pool.
- A session covering two services draws from both contracts, in the proportions recorded
  on the pivot.
- Existing clients keep working after deploy with no admin action.
- `vendor/bin/pint --dirty` clean; the full suite green.

All met. The one behaviour worth restating: a session may now be booked against the same
availed service many times, which is the direct inverse of what the suite asserted
before this phase.

---

## Open questions

Left open deliberately; each needs a product call rather than a technical one. The
current code takes the conservative reading of all three.

- Does the client portal show a parent their remaining hours, or is the budget internal?
  **Currently internal** — nothing was added to the client portal.
- May an admin override the gate and book past the allotment, or does the rule bind
  everyone? **Currently binds everyone.** An admin who needs more hours raises the
  allotment, which is a recorded act; a silent override would not be.
- Should contract hours feed `BillingItem.quantity`, so billing and the contract cannot
  disagree? Related to Phase 9 and worth a separate decision. **Not wired up.**
