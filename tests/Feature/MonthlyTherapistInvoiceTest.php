<?php

use App\Mail\InvoiceResendMail;
use App\Models\Client;
use App\Models\Intake;
use App\Models\Invoice;
use App\Services\MonthlyTherapistInvoiceGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * A therapist's per-client bill for a given day, shaped the way the invoice
 * form writes one.
 *
 * @param  array<int, array{0: string, 1: float, 2: float}>  $lines  [service, quantity, rate]
 */
function therapistBill(int $therapistId, Client $client, string $date, array $lines): Invoice
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
            'name' => $line[0],
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

function namedClient(string $first, string $last): Client
{
    $intake = Intake::factory()->create([
        'child_first_name' => $first,
        'child_last_name' => $last,
    ]);

    return Client::factory()->create(['original_intake_id' => $intake->id]);
}

test('the month-end statement rolls every client bill in the month into one invoice', function () {
    Mail::fake();

    $therapist = therapistUser();
    $samuel = namedClient('Samuel', 'Vandy');
    $jonathan = namedClient('Jonathan', 'Phan');

    therapistBill($therapist->id, $samuel, '2026-06-01', [['BC - Phone Consult', 1.0, 54.08]]);
    therapistBill($therapist->id, $jonathan, '2026-06-05', [['BC - SPPP Documentation', 0.75, 54.08]]);
    therapistBill($therapist->id, $jonathan, '2026-06-09', [['Behavioural Consultant Home Visit', 1.5, 54.08]]);

    $statement = app(MonthlyTherapistInvoiceGenerator::class)
        ->generateFor($therapist->id, Carbon::parse('2026-06-15'));

    expect($statement)->not->toBeNull();
    expect($statement->is_monthly)->toBeTrue();
    expect($statement->status)->toBe('sent');
    expect($statement->period_start->toDateString())->toBe('2026-06-01');
    expect($statement->period_end->toDateString())->toBe('2026-06-30');
    expect($statement->client_id)->toBeNull();

    // 54.08 + 40.56 + 81.12
    expect((float) $statement->total)->toBe(175.76);

    $lines = $statement->services;
    expect($lines)->toHaveCount(3);
    expect($lines[0]['date'])->toBe('2026-06-01');
    expect($lines[0]['client'])->toBe('Samuel Vandy');
    expect($lines[0]['name'])->toBe('BC - Phone Consult');
    expect($lines[1]['client'])->toBe('Jonathan Phan');
    expect($lines[1]['numberOfSessions'])->toBe(0.75);
});

test('fractional hours bill the fraction, not a rounded-down session count', function () {
    $therapist = therapistUser();
    $client = namedClient('Ikeoluwa', 'Abubakre');

    $bill = therapistBill($therapist->id, $client, '2026-06-05', [
        ['Behavioural Consultant Home Visit', 1.5, 54.08],
    ]);

    expect((float) $bill->total)->toBe(81.12);
});

test('bills outside the month are left for their own statement', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = namedClient('Vince', 'Manggad');

    $inside = therapistBill($therapist->id, $client, '2026-06-30', [['BC - Documentation', 1, 100]]);
    $before = therapistBill($therapist->id, $client, '2026-05-31', [['BC - Documentation', 1, 100]]);
    $after = therapistBill($therapist->id, $client, '2026-07-01', [['BC - Documentation', 1, 100]]);

    $statement = app(MonthlyTherapistInvoiceGenerator::class)
        ->generateFor($therapist->id, Carbon::parse('2026-06-01'));

    expect($statement->services)->toHaveCount(1);
    expect($inside->refresh()->monthly_invoice_id)->toBe($statement->id);
    expect($before->refresh()->monthly_invoice_id)->toBeNull();
    expect($after->refresh()->monthly_invoice_id)->toBeNull();
});

test('one therapist never picks up another therapist bills', function () {
    Mail::fake();

    $mine = therapistUser();
    $theirs = therapistUser();
    $client = namedClient('Archer', 'Johansen');

    therapistBill($mine->id, $client, '2026-06-10', [['BC - Documentation', 1, 100]]);
    therapistBill($theirs->id, $client, '2026-06-10', [['BC - Documentation', 1, 200]]);

    $statement = app(MonthlyTherapistInvoiceGenerator::class)
        ->generateFor($mine->id, Carbon::parse('2026-06-01'));

    expect($statement->services)->toHaveCount(1);
    expect((float) $statement->total)->toBe(100.0);
});

test('re-running the month never bills the same work twice', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = namedClient('Hunter', 'Johansen');
    therapistBill($therapist->id, $client, '2026-06-10', [['BC - BDS Planning', 2, 54.08]]);

    $generator = app(MonthlyTherapistInvoiceGenerator::class);
    $first = $generator->generateFor($therapist->id, Carbon::parse('2026-06-01'));
    $second = $generator->generateFor($therapist->id, Carbon::parse('2026-06-01'));

    expect($first)->not->toBeNull();
    expect($second)->toBeNull();
    expect(Invoice::query()->where('is_monthly', true)->where('billed_by', 'therapist')->count())->toBe(1);
});

test('generating the statement is what emails the admins', function () {
    Mail::fake();

    $admin = adminUser();
    $therapist = therapistUser();
    $client = namedClient('Zane', 'Rodriguez');
    therapistBill($therapist->id, $client, '2026-06-10', [['BC - Documentation', 1, 100]]);

    Mail::assertNothingQueued();

    app(MonthlyTherapistInvoiceGenerator::class)
        ->generateFor($therapist->id, Carbon::parse('2026-06-01'));

    Mail::assertQueued(InvoiceResendMail::class, fn ($mail) => $mail->hasTo($admin->email));
});

test('the statement is addressed to the clinic', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = namedClient('Samuel', 'Vandy');
    therapistBill($therapist->id, $client, '2026-06-10', [['BC - Documentation', 1, 100]]);

    $statement = app(MonthlyTherapistInvoiceGenerator::class)
        ->generateFor($therapist->id, Carbon::parse('2026-06-01'));

    expect($statement->bill_to_name)->toBe('Creative Abilities Therapy Services Inc.');
    expect($statement->bill_to_address)->toContain('39 Mahogany Drive SE');
    expect($statement->bill_to_address)->toContain('Calgary, Alberta T3M 2K3');
});

test('the admin sees the statement but never the bills behind it', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = namedClient('Jonathan', 'Phan');
    $bill = therapistBill($therapist->id, $client, '2026-06-10', [['BC - Documentation', 1, 100]]);

    $this->actingAs(adminUser())->get('/admin/invoices')
        ->assertInertia(fn ($page) => $page->has('invoices.data', 0));

    $statement = app(MonthlyTherapistInvoiceGenerator::class)
        ->generateFor($therapist->id, Carbon::parse('2026-06-01'));

    $this->actingAs(adminUser())->get('/admin/invoices')
        ->assertInertia(fn ($page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $statement->id)
        );

    $this->actingAs(adminUser())->get("/admin/invoices/{$bill->id}")->assertNotFound();
    $this->actingAs(adminUser())->get("/admin/invoices/{$statement->id}")->assertOk();
});

test('the therapist keeps seeing their own bills alongside the statement', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = namedClient('Samuel', 'Vandy');
    $bill = therapistBill($therapist->id, $client, '2026-06-10', [['BC - Documentation', 1, 100]]);

    app(MonthlyTherapistInvoiceGenerator::class)
        ->generateFor($therapist->id, Carbon::parse('2026-06-01'));

    $this->actingAs($therapist)->get('/therapist/invoices')
        ->assertInertia(fn ($page) => $page->has('invoices.data', 2));

    $this->actingAs($therapist)->get("/therapist/invoices/{$bill->id}")->assertOk();
});

test('a bill already rolled into a statement can no longer be edited', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = namedClient('Vince', 'Manggad');
    $bill = therapistBill($therapist->id, $client, '2026-06-10', [['BC - Documentation', 1, 100]]);

    $this->actingAs($therapist)->get("/therapist/invoices/{$bill->id}/edit")->assertOk();

    $statement = app(MonthlyTherapistInvoiceGenerator::class)
        ->generateFor($therapist->id, Carbon::parse('2026-06-01'));

    $this->actingAs($therapist)->get("/therapist/invoices/{$bill->id}/edit")->assertNotFound();
    // The statement is generated from its bills, so it is not hand-edited either.
    $this->actingAs($therapist)->get("/therapist/invoices/{$statement->id}/edit")->assertNotFound();
});

test('the scheduled command closes last month', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = namedClient('Archer', 'Johansen');
    therapistBill($therapist->id, $client, '2026-06-15', [['BC - Documentation', 1.25, 80]]);

    $this->travelTo(Carbon::parse('2026-07-01 00:30:00'));

    $this->artisan('invoices:generate-monthly')
        ->expectsOutputToContain('Generated 1 monthly invoice(s) for June 2026.')
        ->assertSuccessful();

    expect(Invoice::query()->where('is_monthly', true)->where('billed_by', 'therapist')->count())->toBe(1);
    expect((float) Invoice::query()->where('is_monthly', true)->where('billed_by', 'therapist')->value('total'))->toBe(100.0);
});

test('the command can be pointed at a specific month', function () {
    Mail::fake();

    $therapist = therapistUser();
    $client = namedClient('Hunter', 'Johansen');
    therapistBill($therapist->id, $client, '2026-06-15', [['BC - Documentation', 1, 100]]);

    $this->artisan('invoices:generate-monthly', ['--month' => '2026-06'])
        ->expectsOutputToContain('Generated 1 monthly invoice(s) for June 2026.')
        ->assertSuccessful();

    expect(Invoice::query()->where('is_monthly', true)->where('billed_by', 'therapist')->count())->toBe(1);
});

test('the command generates a statement for each therapist with work that month', function () {
    Mail::fake();

    $first = therapistUser();
    $second = therapistUser();
    $client = namedClient('Zane', 'Rodriguez');

    therapistBill($first->id, $client, '2026-06-02', [['BC - Documentation', 1, 100]]);
    therapistBill($second->id, $client, '2026-06-03', [['BC - Documentation', 1, 200]]);

    $this->artisan('invoices:generate-monthly', ['--month' => '2026-06'])->assertSuccessful();

    expect(Invoice::query()->where('is_monthly', true)->where('billed_by', 'therapist')->count())->toBe(2);
});

test('a month with no bills produces no statement', function () {
    Mail::fake();

    therapistUser();

    $this->artisan('invoices:generate-monthly', ['--month' => '2026-06'])
        ->expectsOutputToContain('Generated 0 monthly invoice(s) for June 2026.')
        ->assertSuccessful();

    expect(Invoice::query()->where('is_monthly', true)->where('billed_by', 'therapist')->count())->toBe(0);
});
