<?php

use App\Models\Client;
use App\Models\Intake;
use App\Models\Invoice;
use App\Models\InvoiceService;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\MonthlyClientInvoiceGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * A therapist's per-client bill, priced at the therapist's own rate — the
 * record of delivered work the clinic's own invoice is built from.
 *
 * @param  array<int, array{0: InvoiceService, 1: float, 2: float}>  $lines  [service, quantity, therapist rate]
 */
function deliveredWork(int $therapistId, Client $client, string $date, array $lines): Invoice
{
    $invoice = new Invoice([
        'client_id' => $client->id,
        'therapist_id' => $therapistId,
        'billed_by' => 'therapist',
        'invoice_date' => $date,
        'due_date' => $date,
        'tax_percentage' => 0,
        'status' => 'draft',
        'services' => collect($lines)->map(fn (array $line): array => [
            'invoice_service_id' => $line[0]->id,
            'name' => $line[0]->name,
            'description' => null,
            'period' => 'period',
            'numberOfSessions' => $line[1],
            'rate' => '$'.number_format($line[2], 2),
            'rate_numeric' => $line[2],
        ])->all(),
    ]);

    $invoice->calculateTotals();
    $invoice->save();

    return $invoice;
}

function fundedClient(?string $fundingSource): Client
{
    $intake = Intake::factory()->create([
        'child_first_name' => 'Jonathan',
        'child_last_name' => 'Phan',
        'funding_source' => $fundingSource,
    ]);

    return Client::factory()->create(['original_intake_id' => $intake->id]);
}

test('the clinic bills an FSCD client the published FSCD rate, not the therapist rate', function () {
    $therapist = therapistUser();
    $client = fundedClient('BDS-FSCD');
    // Published: FSCD 94.76, private 142.14.
    $service = InvoiceService::factory()->code('occupational-therapist-home-visit')->create();

    // The therapist has their own, higher rate and bills the clinic at it.
    $teamMember = TeamMember::factory()->create(['user_id' => $therapist->id]);
    $teamMember->invoiceServiceRates()->attach($service->id, ['rate_fscd' => '200.00']);

    deliveredWork($therapist->id, $client, '2026-06-10', [[$service, 2, 200.00]]);

    $invoice = app(MonthlyClientInvoiceGenerator::class)
        ->generateFor($client->id, Carbon::parse('2026-06-01'));

    expect($invoice)->not->toBeNull();
    expect($invoice->billed_by)->toBe('admin');
    expect($invoice->services[0]['rate_numeric'])->toBe(94.76);
    expect((float) $invoice->total)->toBe(189.52);
});

test('a private client is billed the private rate for the same work', function () {
    $therapist = therapistUser();
    $client = fundedClient('private');
    $service = InvoiceService::factory()->code('occupational-therapist-home-visit')->create();

    deliveredWork($therapist->id, $client, '2026-06-10', [[$service, 1, 50.00]]);

    $invoice = app(MonthlyClientInvoiceGenerator::class)
        ->generateFor($client->id, Carbon::parse('2026-06-01'));

    expect($invoice->services[0]['rate_numeric'])->toBe(142.14);
    expect((float) $invoice->total)->toBe(142.14);
});

test('an insurance client bills the private/insurance column', function () {
    $therapist = therapistUser();
    $client = fundedClient('Insurance');
    $service = InvoiceService::factory()->code('behavioural-consultant-home-visit')->create();

    deliveredWork($therapist->id, $client, '2026-06-10', [[$service, 1, 10.00]]);

    $invoice = app(MonthlyClientInvoiceGenerator::class)
        ->generateFor($client->id, Carbon::parse('2026-06-01'));

    expect($invoice->services[0]['rate_numeric'])->toBe(115.88);
});

test('the invoice covers the whole month and carries the date and client per line', function () {
    $therapist = therapistUser();
    $client = fundedClient('BDS-FSCD');
    $service = InvoiceService::factory()->code('slp-documentation')->create();

    deliveredWork($therapist->id, $client, '2026-06-01', [[$service, 1, 1]]);
    deliveredWork($therapist->id, $client, '2026-06-30', [[$service, 1, 1]]);
    deliveredWork($therapist->id, $client, '2026-07-01', [[$service, 1, 1]]);

    $invoice = app(MonthlyClientInvoiceGenerator::class)
        ->generateFor($client->id, Carbon::parse('2026-06-01'));

    expect($invoice->services)->toHaveCount(2);
    expect($invoice->period_start->toDateString())->toBe('2026-06-01');
    expect($invoice->period_end->toDateString())->toBe('2026-06-30');
    expect($invoice->services[0]['date'])->toBe('2026-06-01');
    expect($invoice->services[1]['date'])->toBe('2026-06-30');
    expect($invoice->services[0]['client'])->toBe('Jonathan Phan');
});

test('work from every therapist lands on the one client invoice', function () {
    $first = therapistUser();
    $second = therapistUser();
    $client = fundedClient('BDS-FSCD');
    $service = InvoiceService::factory()->code('slp-documentation')->create();

    deliveredWork($first->id, $client, '2026-06-02', [[$service, 1, 1]]);
    deliveredWork($second->id, $client, '2026-06-03', [[$service, 1, 1]]);

    $invoices = app(MonthlyClientInvoiceGenerator::class)
        ->generateForMonth(Carbon::parse('2026-06-01'));

    expect($invoices)->toHaveCount(1);
    expect($invoices->first()->services)->toHaveCount(2);
});

test('a line the rate card no longer carries keeps the rate it was delivered at', function () {
    $therapist = therapistUser();
    $client = fundedClient('BDS-FSCD');

    $invoice = new Invoice([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'billed_by' => 'therapist',
        'invoice_date' => '2026-06-10',
        'due_date' => '2026-06-10',
        'tax_percentage' => 0,
        'status' => 'draft',
        'services' => [[
            // A hand-typed "Other" line: no rate-card id to re-price from.
            'invoice_service_id' => null,
            'name' => 'Special arrangement',
            'description' => null,
            'period' => 'period',
            'numberOfSessions' => 2,
            'rate' => '$60.00',
            'rate_numeric' => 60.0,
        ]],
    ]);
    $invoice->calculateTotals();
    $invoice->save();

    $generated = app(MonthlyClientInvoiceGenerator::class)
        ->generateFor($client->id, Carbon::parse('2026-06-01'));

    expect((float) $generated->services[0]['rate_numeric'])->toBe(60.0);
    expect((float) $generated->total)->toBe(120.0);
});

test('the client invoice is a draft, so no family is billed without review', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = fundedClient('BDS-FSCD');
    $service = InvoiceService::factory()->code('slp-documentation')->create();
    deliveredWork($therapist->id, $client, '2026-06-10', [[$service, 1, 1]]);

    $invoice = app(MonthlyClientInvoiceGenerator::class)
        ->generateFor($client->id, Carbon::parse('2026-06-01'));

    expect($invoice->status)->toBe('draft');
    Mail::assertNothingQueued();
    Mail::assertNothingSent();
});

test('a client is never invoiced twice for the same month', function () {
    $therapist = therapistUser();
    $client = fundedClient('BDS-FSCD');
    $service = InvoiceService::factory()->code('slp-documentation')->create();
    deliveredWork($therapist->id, $client, '2026-06-10', [[$service, 1, 1]]);

    $generator = app(MonthlyClientInvoiceGenerator::class);
    $first = $generator->generateFor($client->id, Carbon::parse('2026-06-01'));
    $second = $generator->generateFor($client->id, Carbon::parse('2026-06-01'));

    expect($first)->not->toBeNull();
    expect($second)->toBeNull();
    expect(Invoice::query()->where('billed_by', 'admin')->count())->toBe(1);
});

test('a client with no delivered work that month is not invoiced', function () {
    $client = fundedClient('BDS-FSCD');

    $invoice = app(MonthlyClientInvoiceGenerator::class)
        ->generateFor($client->id, Carbon::parse('2026-06-01'));

    expect($invoice)->toBeNull();
});

test('the family sees the generated invoice and the therapist bill behind it stays hidden', function () {
    $therapist = therapistUser();
    $intake = Intake::factory()->create(['funding_source' => 'BDS-FSCD']);
    $client = Client::factory()->create([
        'original_intake_id' => $intake->id,
        'user_id' => User::factory()->client()->create()->id,
    ]);
    $client->load('user');

    $service = InvoiceService::factory()->code('slp-documentation')->create();
    $bill = deliveredWork($therapist->id, $client, '2026-06-10', [[$service, 1, 1]]);

    $generated = app(MonthlyClientInvoiceGenerator::class)
        ->generateFor($client->id, Carbon::parse('2026-06-01'));

    $this->actingAs($client->user)->get("/client/invoices/{$generated->id}")->assertOk();
    $this->actingAs($client->user)->get("/client/invoices/{$bill->id}")->assertNotFound();
});

test('the command closes both sides of the ledger', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = fundedClient('BDS-FSCD');
    $service = InvoiceService::factory()->code('occupational-therapist-home-visit')->create();

    deliveredWork($therapist->id, $client, '2026-06-10', [[$service, 1, 200.00]]);

    $this->artisan('invoices:generate-monthly', ['--month' => '2026-06'])
        ->expectsOutputToContain('Generated 1 monthly invoice(s) for June 2026.')
        ->expectsOutputToContain('Generated 1 client invoice(s) for June 2026.')
        ->assertSuccessful();

    // The therapist billed the clinic at their rate; the clinic bills the
    // family at its own published rate.
    expect((float) Invoice::query()->where('billed_by', 'therapist')->where('is_monthly', true)->value('total'))->toBe(200.0);
    expect((float) Invoice::query()->where('billed_by', 'admin')->where('is_monthly', true)->value('total'))->toBe(94.76);
});

test('the command can close one side of the ledger only', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = fundedClient('BDS-FSCD');
    $service = InvoiceService::factory()->code('slp-documentation')->create();
    deliveredWork($therapist->id, $client, '2026-06-10', [[$service, 1, 1]]);

    $this->artisan('invoices:generate-monthly', ['--month' => '2026-06', '--only' => 'client'])
        ->assertSuccessful();

    expect(Invoice::query()->where('billed_by', 'admin')->where('is_monthly', true)->count())->toBe(1);
    expect(Invoice::query()->where('billed_by', 'therapist')->where('is_monthly', true)->count())->toBe(0);
});

test('the command rejects an unknown side', function () {
    $this->artisan('invoices:generate-monthly', ['--only' => 'nonsense'])->assertFailed();
});
