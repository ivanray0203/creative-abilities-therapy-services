<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;
use App\Models\ServiceContract;
use App\Models\ServiceOffering;

/**
 * Phase 21 — the therapist's hour-tracking sheet: one section per service,
 * one row per contract, a column per month of the window.
 *
 * @see app/Services/HourTrackingReport.php
 */

/**
 * Book a session straight onto the ledger for a given month.
 *
 * These tests are about how hours are *reported*, not how they are drawn, so
 * the pivot is written directly rather than posted through the session form —
 * that path has its own suite in ServiceContractLedgerTest.
 */
function drawHours(ServiceContract $contract, string $date, float $hours, string $status = 'scheduled'): ScheduleSession
{
    $service = $contract->clientService;

    $session = ScheduleSession::factory()->create([
        'client_id' => $service->client_id,
        'therapist_id' => $contract->therapist_id,
        'service_id' => $service->service_id,
        'scheduled_start' => "{$date} 09:00:00",
        'scheduled_end' => "{$date} 10:00:00",
        'status' => $status,
    ]);

    $session->clientServices()->attach($service->id, [
        'hours' => $hours,
        'service_contract_id' => $contract->id,
    ]);

    return $session;
}

test('the sheet lists a therapist contract with its monthly draws and balance', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: [
        'allotted_hours' => 40,
        'funding_code' => 'SS',
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
    ]);

    $contract = $service->contracts()->first();

    drawHours($contract, '2026-03-04', 2.5);
    drawHours($contract, '2026-03-18', 1.5);
    drawHours($contract, '2026-05-06', 3.0);

    $this->actingAs($therapist)
        ->get('/therapist/hour-tracking?from=2026-01-01&to=2026-06-30')
        ->assertOk()
        ->assertInertia(function ($page) {
            $page->component('hour-tracking/index')
                ->where('report.months.0.label', 'Jan')
                ->where('report.months.5.label', 'Jun')
                ->count('report.months', 6)
                ->count('report.sections', 1)
                ->where('report.sections.0.rows.0.funding_code', 'SS')
                ->where('report.sections.0.rows.0.allotted_hours', 40)
                // March's two visits add up; May stands alone; the rest are
                // blank rather than zero.
                ->where('report.sections.0.rows.0.months.2026-03', 4)
                ->where('report.sections.0.rows.0.months.2026-05', 3)
                ->where('report.sections.0.rows.0.months.2026-02', null)
                ->where('report.sections.0.rows.0.remaining_hours', 33)
                ->where('report.totals.used_hours', 7);
        });
});

test('a month column exists for every month in the range and no others', function () {
    $therapist = therapistUser();

    $this->actingAs($therapist)
        ->get('/therapist/hour-tracking?from=2026-06-15&to=2026-08-02')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('report.months', 3)
            ->where('report.months.0.key', '2026-06')
            ->where('report.months.2.key', '2026-08')
        );
});

test('cancelled and no-show sessions are left out of the monthly figures', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: [
        'allotted_hours' => 10,
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
    ]);

    $contract = $service->contracts()->first();

    drawHours($contract, '2026-04-01', 2.0);
    drawHours($contract, '2026-04-08', 3.0, 'cancelled');
    drawHours($contract, '2026-04-15', 1.0, 'no_show');

    $this->actingAs($therapist)
        ->get('/therapist/hour-tracking?from=2026-04-01&to=2026-04-30')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.sections.0.rows.0.months.2026-04', 2)
            ->where('report.sections.0.rows.0.remaining_hours', 8)
        );
});

test('a therapist sees only the contracts they are authorized on', function () {
    $mine = therapistUser();
    $theirs = therapistUser();

    $client = Client::factory()->create(['primary_therapist_id' => $mine->id]);
    contractedService($client, $mine, contract: ['period_start' => '2026-01-01', 'period_end' => '2026-12-31']);
    contractedService($client, $theirs, contract: ['period_start' => '2026-01-01', 'period_end' => '2026-12-31']);

    $this->actingAs($mine)
        ->get('/therapist/hour-tracking?from=2026-01-01&to=2026-12-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('report.sections', 1)
            ->count('report.sections.0.rows', 1)
        );
});

test('contracts are grouped into a section per service', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);

    $speech = ServiceOffering::factory()->create(['name' => 'Speech-Language Therapy']);
    $occupational = ServiceOffering::factory()->create(['name' => 'Occupational Therapy']);

    contractedService($client, $therapist, ['service_id' => $speech->id], ['period_start' => '2026-01-01', 'period_end' => '2026-12-31']);
    contractedService($client, $therapist, ['service_id' => $occupational->id], ['period_start' => '2026-01-01', 'period_end' => '2026-12-31']);

    $this->actingAs($therapist)
        ->get('/therapist/hour-tracking?from=2026-01-01&to=2026-12-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('report.sections', 2)
            // Alphabetical, so the sheet reads the same way twice running.
            ->where('report.sections.0.service', 'Occupational Therapy')
            ->where('report.sections.1.service', 'Speech-Language Therapy')
        );
});

test('a contract whose period misses the range entirely is left off', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);

    contractedService($client, $therapist, contract: [
        'period_start' => '2025-01-01',
        'period_end' => '2025-06-30',
    ]);

    $this->actingAs($therapist)
        ->get('/therapist/hour-tracking?from=2026-01-01&to=2026-12-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('report.sections', 0));
});

test('a contract that merely overlaps the range still shows, with that range hours', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: [
        'allotted_hours' => 20,
        'period_start' => '2025-07-01',
        'period_end' => '2026-06-30',
    ]);

    $contract = $service->contracts()->first();

    drawHours($contract, '2025-09-10', 4.0);
    drawHours($contract, '2026-02-10', 3.0);

    $this->actingAs($therapist)
        ->get('/therapist/hour-tracking?from=2026-01-01&to=2026-03-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('report.sections.0.rows', 1)
            ->where('report.sections.0.rows.0.months.2026-02', 3)
            // The balance is the contract's own, not the window's: 4 hours
            // drawn before the window still came out of the pool.
            ->where('report.sections.0.rows.0.remaining_hours', 13)
        );
});

test('the range defaults to the current calendar year', function () {
    $this->travelTo('2026-08-30');

    $this->actingAs(therapistUser())
        ->get('/therapist/hour-tracking')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.from', '2026-01-01')
            ->where('filters.to', '2026-12-31')
            ->count('report.months', 12)
        );
});

test('a backwards range is swapped rather than rejected', function () {
    $this->actingAs(therapistUser())
        ->get('/therapist/hour-tracking?from=2026-06-30&to=2026-04-01')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.from', '2026-04-01')
            ->where('filters.to', '2026-06-30')
        );
});

test('the sheet downloads as a PDF over the same range', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: [
        'allotted_hours' => 40,
        'funding_code' => 'BDS',
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
    ]);

    drawHours($service->contracts()->first(), '2026-03-04', 2.5);

    $response = $this->actingAs($therapist)
        ->get('/therapist/hour-tracking/pdf?from=2026-01-01&to=2026-06-30')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

test('an aide is sent to their own hours page instead', function () {
    // `aide:never` redirects rather than aborting, the way every other
    // therapist-only route on this side of the portal does.
    $this->actingAs(aideUser())
        ->get('/therapist/hour-tracking')
        ->assertRedirect('/therapist/hours');
});

test('an admin issues a contract with a funding code, and an unknown one is rejected', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = ClientService::factory()->for($client)->create([
        'therapist_id' => $therapist->id,
        'service_id' => ServiceOffering::factory(),
    ]);

    $admin = adminUser();
    $url = "/admin/clients/{$service->client_id}/services/{$service->id}/contracts";

    $payload = [
        'allotted_hours' => 40,
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
    ];

    $this->actingAs($admin)
        ->post($url, [...$payload, 'funding_code' => 'BDS/split'])
        ->assertSessionHasNoErrors();

    expect(ServiceContract::first()->funding_code)->toBe('BDS/split');

    $this->actingAs($admin)
        ->post($url, [
            ...$payload,
            'funding_code' => 'Cheque',
            'period_start' => '2027-01-01',
            'period_end' => '2027-12-31',
        ])
        ->assertSessionHasErrors('funding_code');
});

test('admin sees every therapist contract, with a therapist on each row', function () {
    $first = therapistUser();
    $second = therapistUser();

    $client = Client::factory()->create(['primary_therapist_id' => $first->id]);
    $speech = ServiceOffering::factory()->create(['name' => 'Speech-Language Therapy']);

    contractedService($client, $first, ['service_id' => $speech->id], [
        'period_start' => '2026-01-01', 'period_end' => '2026-12-31',
    ]);
    contractedService($client, $second, ['service_id' => $speech->id], [
        'period_start' => '2026-01-01', 'period_end' => '2026-12-31',
    ]);

    $this->actingAs(adminUser())
        ->get('/admin/hour-tracking?from=2026-01-01&to=2026-12-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/hour-tracking')
            ->count('report.sections', 1)
            ->count('report.sections.0.rows', 2)
            ->where('filters.therapist_id', null)
            ->where(
                'report.sections.0.rows.0.therapist_name',
                trim("{$first->first_name} {$first->last_name}"),
            )
        );
});

test('admin can narrow the sheet to one therapist', function () {
    $wanted = therapistUser();
    $other = therapistUser();

    $client = Client::factory()->create(['primary_therapist_id' => $wanted->id]);
    contractedService($client, $wanted, contract: ['period_start' => '2026-01-01', 'period_end' => '2026-12-31']);
    contractedService($client, $other, contract: ['period_start' => '2026-01-01', 'period_end' => '2026-12-31']);

    $this->actingAs(adminUser())
        ->get("/admin/hour-tracking?from=2026-01-01&to=2026-12-31&therapist_id={$wanted->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('report.sections.0.rows', 1)
            ->where('filters.therapist_id', $wanted->id)
            ->where(
                'report.sections.0.rows.0.therapist_name',
                trim("{$wanted->first_name} {$wanted->last_name}"),
            )
        );
});

test('the therapist filter offers only therapists who hold a contract', function () {
    $withContract = therapistUser();
    therapistUser();

    $client = Client::factory()->create(['primary_therapist_id' => $withContract->id]);
    contractedService($client, $withContract, contract: ['period_start' => '2026-01-01', 'period_end' => '2026-12-31']);

    $this->actingAs(adminUser())
        ->get('/admin/hour-tracking')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('therapists', 1)
            ->where('therapists.0.id', $withContract->id)
        );
});

test('a therapist cannot reach the admin sheet', function () {
    $this->actingAs(therapistUser())
        ->get('/admin/hour-tracking')
        ->assertRedirect();
});

test('the admin sheet downloads as a PDF across every therapist', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $service = contractedService($client, $therapist, contract: [
        'allotted_hours' => 40,
        'funding_code' => 'SS',
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
    ]);

    drawHours($service->contracts()->first(), '2026-03-04', 2.5);

    $response = $this->actingAs(adminUser())
        ->get('/admin/hour-tracking/pdf?from=2026-01-01&to=2026-06-30')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($response->getContent())->toStartWith('%PDF');
});
