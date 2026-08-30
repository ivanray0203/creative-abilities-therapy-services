<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:mark-overdue')->daily();
// Phase 20 — reconciles each service contract's cached status with its
// period and its balance. Nightly is soon enough: the booking gate reads the
// period and the hours directly, so a stale badge is the worst this can cost.
Schedule::command('contracts:sweep')->daily();
// Closes last month's billing on the 1st, both sides of the ledger: each
// therapist's per-client bills become one statement sent to the admins, and
// each client gets the clinic's own invoice as a draft to review.
//
// Requires a scheduler runner (`schedule:work`, or cron calling
// `schedule:run` every minute) — declaring it here does not fire it.
Schedule::command('invoices:generate-monthly')->monthlyOn(1, '00:30');
