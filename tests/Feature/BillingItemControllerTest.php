<?php

use App\Mail\InvoiceResendMail;
use App\Models\BillingItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceService;
use App\Models\ScheduleSession;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('a therapist only sees billing items they raised', function () {
    $therapist = therapistUser();
    BillingItem::factory()->create(['therapist_id' => $therapist->id]);
    BillingItem::factory()->create(['therapist_id' => therapistUser()->id]);
    // Raised for this therapist, but by an admin — the clinic's line, not theirs.
    BillingItem::factory()->create([
        'therapist_id' => $therapist->id,
        'issued_by_id' => adminUser()->id,
    ]);

    $response = $this->actingAs($therapist)->get('/therapist/billing');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('billing/index')
        ->has('items.data', 1)
        ->where('items.data.0.issued_by_id', $therapist->id)
    );
});

test('the billing stats separate unbilled items from invoiced ones', function () {
    $therapist = therapistUser();
    $invoice = Invoice::factory()->create();

    BillingItem::factory()->create(['therapist_id' => $therapist->id, 'amount' => 120.50]);
    BillingItem::factory()->create(['therapist_id' => $therapist->id, 'amount' => 79.50]);
    BillingItem::factory()->billed($invoice->id)->create(['therapist_id' => $therapist->id, 'amount' => 300]);

    $response = $this->actingAs($therapist)->get('/therapist/billing');

    $response->assertInertia(fn ($page) => $page
        ->where('stats.unbilled_count', 2)
        ->where('stats.unbilled_total', fn ($total) => (float) $total === 200.0)
        ->where('stats.billed_total', fn ($total) => (float) $total === 300.0)
    );
});

test('the status filter narrows the ledger to unbilled or invoiced items', function () {
    $therapist = therapistUser();
    $invoice = Invoice::factory()->create();
    BillingItem::factory()->create(['therapist_id' => $therapist->id]);
    BillingItem::factory()->billed($invoice->id)->create(['therapist_id' => $therapist->id]);

    $this->actingAs($therapist)->get('/therapist/billing?status=unbilled')
        ->assertInertia(fn ($page) => $page->has('items.data', 1)->where('items.data.0.invoice_id', null));

    $this->actingAs($therapist)->get('/therapist/billing?status=billed')
        ->assertInertia(fn ($page) => $page->has('items.data', 1)->where('items.data.0.invoice_id', $invoice->id));
});

test('creating a bill saves one row per service line', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create();
    $service = InvoiceService::factory()->create();

    $this->actingAs($therapist)->post('/therapist/billing', [
        'client_id' => $client->id,
        'notes' => 'Two visits this week.',
        'services' => [
            ['invoice_service_id' => $service->id, 'name' => $service->name, 'quantity' => 1.5, 'rate' => 100],
            ['invoice_service_id' => null, 'name' => 'Travel Time', 'quantity' => 2, 'rate' => 25.50],
        ],
    ])->assertRedirect('/therapist/billing')->assertSessionHasNoErrors();

    expect(BillingItem::count())->toBe(2);

    $first = BillingItem::query()->where('service_name', $service->name)->firstOrFail();
    expect($first->therapist_id)->toBe($therapist->id)
        ->and($first->issued_by_id)->toBe($therapist->id)
        ->and($first->client_id)->toBe($client->id)
        ->and($first->invoice_service_id)->toBe($service->id)
        ->and((float) $first->amount)->toBe(150.0)
        ->and($first->invoice_id)->toBeNull()
        ->and($first->notes)->toBe('Two visits this week.')
        ->and($first->billing_number)->toStartWith('BIL-'.now()->year.'-');

    $second = BillingItem::query()->where('service_name', 'Travel Time')->firstOrFail();
    expect((float) $second->amount)->toBe(51.0)
        ->and($second->billing_number)->not->toBe($first->billing_number);
});

test('a bill cannot be raised against another client\'s session', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create();
    $otherSession = ScheduleSession::factory()->create();

    $this->actingAs($therapist)->post('/therapist/billing', [
        'client_id' => $client->id,
        'session_id' => $otherSession->id,
        'services' => [['name' => 'Home Visit', 'quantity' => 1, 'rate' => 100]],
    ])->assertSessionHasErrors('session_id');

    expect(BillingItem::count())->toBe(0);
});

test('a therapist can remove an unbilled item but not one an invoice has claimed', function () {
    $therapist = therapistUser();
    $unbilled = BillingItem::factory()->create(['therapist_id' => $therapist->id]);
    $invoiced = BillingItem::factory()->billed(Invoice::factory()->create()->id)->create(['therapist_id' => $therapist->id]);

    $this->actingAs($therapist)->delete("/therapist/billing/{$unbilled->id}");
    expect(BillingItem::find($unbilled->id))->toBeNull();

    $this->actingAs($therapist)->delete("/therapist/billing/{$invoiced->id}");
    expect(BillingItem::find($invoiced->id))->not->toBeNull();
});

test('a therapist cannot remove a billing item they did not raise', function () {
    $therapist = therapistUser();
    $othersItem = BillingItem::factory()->create(['therapist_id' => therapistUser()->id]);
    // Raised for them by an admin, so still not theirs to remove.
    $adminsItem = BillingItem::factory()->create([
        'therapist_id' => $therapist->id,
        'issued_by_id' => adminUser()->id,
    ]);

    $this->actingAs($therapist)->delete("/therapist/billing/{$othersItem->id}")->assertNotFound();
    $this->actingAs($therapist)->delete("/therapist/billing/{$adminsItem->id}")->assertNotFound();

    expect(BillingItem::count())->toBe(2);
});

test('the create form only offers clients the therapist has delivered a session to', function () {
    $therapist = therapistUser();
    $ownClient = Client::factory()->create();
    ScheduleSession::factory()->create([
        'therapist_id' => $therapist->id,
        'client_id' => $ownClient->id,
        'status' => 'completed',
    ]);
    ScheduleSession::factory()->create(['status' => 'completed']);

    $response = $this->actingAs($therapist)->get('/therapist/billing/create');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('billing/create')
        ->has('clients', 1)
        ->where('clients.0.id', $ownClient->id)
        ->has('services')
    );
});

test('an admin never sees a therapist\'s own billing items', function () {
    $admin = adminUser();
    BillingItem::factory()->create(['therapist_id' => therapistUser()->id]);
    BillingItem::factory()->create(['therapist_id' => therapistUser()->id]);
    // The clinic's own line: raised by an admin, so the admin side sees it.
    $ownLine = BillingItem::factory()->create([
        'therapist_id' => therapistUser()->id,
        'issued_by_id' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get('/admin/billing');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('billing/index')
        ->where('role', 'admin')
        ->has('items.data', 1)
        ->where('items.data.0.id', $ownLine->id)
    );
});

test('an admin sees a line another admin raised', function () {
    $line = BillingItem::factory()->create([
        'therapist_id' => therapistUser()->id,
        'issued_by_id' => adminUser()->id,
    ]);

    $this->actingAs(adminUser())->get('/admin/billing')
        ->assertInertia(fn ($page) => $page->has('items.data', 1)->where('items.data.0.id', $line->id));
});

test('an admin cannot delete a therapist\'s billing item', function () {
    $item = BillingItem::factory()->create(['therapist_id' => therapistUser()->id]);

    $this->actingAs(adminUser())->delete("/admin/billing/{$item->id}")->assertNotFound();

    expect(BillingItem::find($item->id))->not->toBeNull();
});

test('an admin raises a bill for the therapist they pick', function () {
    $therapist = therapistUser();
    $client = Client::factory()->create();
    $admin = adminUser();

    $this->actingAs($admin)->post('/admin/billing', [
        'therapist_id' => $therapist->id,
        'client_id' => $client->id,
        'services' => [['name' => 'Home Visit', 'quantity' => 2, 'rate' => 90]],
    ])->assertRedirect('/admin/billing')->assertSessionHasNoErrors();

    $item = BillingItem::firstOrFail();
    // The work is the therapist's; raising the bill is the admin's.
    expect($item->therapist_id)->toBe($therapist->id)
        ->and($item->issued_by_id)->toBe($admin->id)
        ->and((float) $item->amount)->toBe(180.0);
});

test('an admin must say whose bill it is', function () {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())->post('/admin/billing', [
        'client_id' => $client->id,
        'services' => [['name' => 'Home Visit', 'quantity' => 1, 'rate' => 90]],
    ])->assertSessionHasErrors('therapist_id');

    expect(BillingItem::count())->toBe(0);
});

test('the admin create form offers the therapists a bill can be raised for', function () {
    $therapist = therapistUser();

    $response = $this->actingAs(adminUser())->get('/admin/billing/create');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('billing/create')
        ->where('role', 'admin')
        ->has('therapists', 1)
        ->where('therapists.0.id', $therapist->id)
    );
});

test('a therapist is never offered a therapist picker', function () {
    $this->actingAs(therapistUser())->get('/therapist/billing/create')
        ->assertInertia(fn ($page) => $page->has('therapists', 0));
});

test('a client cannot reach the billing pages', function () {
    $this->actingAs(clientWithUser()->user)->get('/therapist/billing')->assertRedirect();
});

test('the admin invoices list offers only clients with billing left to invoice', function () {
    $admin = adminUser();
    $waiting = Client::factory()->create();
    $alreadyInvoiced = Client::factory()->create();
    $therapistsOnly = Client::factory()->create();

    BillingItem::factory()->create(['client_id' => $waiting->id, 'issued_by_id' => $admin->id]);
    BillingItem::factory()->billed(Invoice::factory()->create()->id)
        ->create(['client_id' => $alreadyInvoiced->id, 'issued_by_id' => $admin->id]);
    // A therapist's own bill is not the clinic's to invoice a family for.
    BillingItem::factory()->create(['client_id' => $therapistsOnly->id]);

    $this->actingAs($admin)->get('/admin/invoices')->assertInertia(fn ($page) => $page
        ->has('billableClients', 1)
        ->where('billableClients.0.id', $waiting->id)
    );
});

test('generating an invoice bills every item in the period and claims them', function () {
    Mail::fake();

    $admin = adminUser();
    $client = clientWithUser();

    $inPeriod = BillingItem::factory()->count(2)->sequence(
        ['quantity' => 2, 'rate' => 100, 'amount' => 200, 'created_at' => now()->subDays(5)],
        ['quantity' => 1.5, 'rate' => 80, 'amount' => 120, 'created_at' => now()->subDay()],
    )->create(['client_id' => $client->id, 'issued_by_id' => $admin->id]);

    $outOfPeriod = BillingItem::factory()->create([
        'client_id' => $client->id,
        'issued_by_id' => $admin->id,
        'created_at' => now()->subMonths(2),
    ]);

    $this->actingAs($admin)->post('/admin/invoices/generate-from-billing', [
        'client_id' => $client->id,
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::query()->where('client_id', $client->id)->firstOrFail();

    expect($invoice->billed_by)->toBe('admin')
        // Generating is the decision to bill, so it goes out at once.
        ->and($invoice->status)->toBe('sent')
        ->and($invoice->issued_by_id)->toBe($admin->id)
        ->and($invoice->services)->toHaveCount(2)
        ->and((float) $invoice->sub_total)->toBe(320.0)
        ->and((float) $invoice->total)->toBe(320.0)
        // Same line-item shape the invoice form writes.
        ->and($invoice->services[0])->toHaveKeys(['name', 'numberOfSessions', 'rate', 'rate_numeric']);

    foreach ($inPeriod as $item) {
        expect($item->fresh()->invoice_id)->toBe($invoice->id);
    }

    expect($outOfPeriod->fresh()->invoice_id)->toBeNull();

    Mail::assertQueued(InvoiceResendMail::class, fn ($mail) => $mail->hasTo($client->user->email)
        && $mail->invoice->id === $invoice->id);
});

test('a billing item is never invoiced twice', function () {
    $admin = adminUser();
    $client = Client::factory()->create();
    BillingItem::factory()->create(['client_id' => $client->id, 'issued_by_id' => $admin->id]);

    $dates = [
        'client_id' => $client->id,
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ];

    $this->actingAs($admin)->post('/admin/invoices/generate-from-billing', $dates)->assertSessionHasNoErrors();
    // Nothing is left waiting, so a second run has nothing to bill.
    $this->actingAs($admin)->post('/admin/invoices/generate-from-billing', $dates)->assertSessionHasErrors('client_id');

    expect(Invoice::query()->where('client_id', $client->id)->count())->toBe(1);
});

test('generating an invoice ignores a therapist\'s own bills', function () {
    $client = Client::factory()->create();
    BillingItem::factory()->create(['client_id' => $client->id]);

    $this->actingAs(adminUser())->post('/admin/invoices/generate-from-billing', [
        'client_id' => $client->id,
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ])->assertSessionHasErrors('client_id');

    expect(Invoice::query()->where('client_id', $client->id)->count())->toBe(0);
});

test('the end date cannot fall before the start date', function () {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())->post('/admin/invoices/generate-from-billing', [
        'client_id' => $client->id,
        'date_start' => now()->toDateString(),
        'date_end' => now()->subWeek()->toDateString(),
    ])->assertSessionHasErrors('date_end');
});

test('a therapist cannot generate an invoice from billing', function () {
    $this->actingAs(therapistUser())->post('/admin/invoices/generate-from-billing', [
        'client_id' => Client::factory()->create()->id,
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ])->assertRedirect();
});

test('a therapist invoices the clinic for everything they billed in the period', function () {
    Mail::fake();

    $therapist = therapistUser();
    $admin = adminUser();
    $firstChild = Client::factory()->create();
    $secondChild = Client::factory()->create();

    $mine = BillingItem::factory()->count(2)->sequence(
        ['client_id' => $firstChild->id, 'quantity' => 2, 'rate' => 60, 'amount' => 120],
        ['client_id' => $secondChild->id, 'quantity' => 1, 'rate' => 45, 'amount' => 45],
    )->create(['therapist_id' => $therapist->id, 'created_at' => now()->subDays(3)]);

    // Another therapist's line, and one an admin raised: neither is theirs.
    $othersLine = BillingItem::factory()->create(['created_at' => now()->subDays(3)]);
    $clinicsLine = BillingItem::factory()->create([
        'therapist_id' => $therapist->id,
        'issued_by_id' => $admin->id,
        'created_at' => now()->subDays(3),
    ]);

    $this->actingAs($therapist)->post('/therapist/invoices/generate-from-billing', [
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::query()->where('therapist_id', $therapist->id)->firstOrFail();

    expect($invoice->billed_by)->toBe('therapist')
        ->and($invoice->status)->toBe('sent')
        // A statement is what the clinic receives, so the admin side sees it.
        ->and($invoice->is_monthly)->toBeTrue()
        ->and($invoice->client_id)->toBeNull()
        ->and($invoice->services)->toHaveCount(2)
        ->and((float) $invoice->total)->toBe(165.0);

    foreach ($mine as $item) {
        expect($item->fresh()->invoice_id)->toBe($invoice->id);
    }

    expect($othersLine->fresh()->invoice_id)->toBeNull()
        ->and($clinicsLine->fresh()->invoice_id)->toBeNull();

    // The clinic owes it, so the admins are the ones told.
    Mail::assertQueued(InvoiceResendMail::class, fn ($mail) => $mail->hasTo($admin->email));
});

test('a therapist does not name a client when invoicing', function () {
    $therapist = therapistUser();
    BillingItem::factory()->create(['therapist_id' => $therapist->id]);

    $this->actingAs($therapist)->post('/therapist/invoices/generate-from-billing', [
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    expect(Invoice::query()->where('therapist_id', $therapist->id)->count())->toBe(1);
});

test('a therapist with nothing left to invoice is told so', function () {
    $therapist = therapistUser();
    BillingItem::factory()->create([
        'therapist_id' => $therapist->id,
        'created_at' => now()->subMonths(3),
    ]);

    $this->actingAs($therapist)->post('/therapist/invoices/generate-from-billing', [
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ])->assertSessionHasErrors('date_start');

    expect(Invoice::query()->where('therapist_id', $therapist->id)->count())->toBe(0);
});

test('a therapist invoice is generated once for the same lines', function () {
    $therapist = therapistUser();
    BillingItem::factory()->create(['therapist_id' => $therapist->id]);

    $dates = [
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ];

    $this->actingAs($therapist)->post('/therapist/invoices/generate-from-billing', $dates)->assertSessionHasNoErrors();
    $this->actingAs($therapist)->post('/therapist/invoices/generate-from-billing', $dates)->assertSessionHasErrors('date_start');

    expect(Invoice::query()->where('therapist_id', $therapist->id)->count())->toBe(1);
});

test('a generated therapist invoice is filed on Drive as a PDF', function () {
    Mail::fake();
    Storage::fake('public');

    $therapist = therapistUser();
    BillingItem::factory()->create(['therapist_id' => $therapist->id]);

    $this->actingAs($therapist)->post('/therapist/invoices/generate-from-billing', [
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::query()->where('therapist_id', $therapist->id)->firstOrFail();

    expect($invoice->not_signed_invoice)->not->toBeNull();

    $filed = Storage::disk('public')->allFiles('drive/CATS/invoice/not-signed');
    expect($filed)->toHaveCount(1)
        ->and(Storage::disk('public')->get($filed[0]))->toStartWith('%PDF');
});

test('a generated client invoice is filed on Drive as a PDF', function () {
    Mail::fake();
    Storage::fake('public');

    $admin = adminUser();
    $client = Client::factory()->create();
    BillingItem::factory()->create(['client_id' => $client->id, 'issued_by_id' => $admin->id]);

    $this->actingAs($admin)->post('/admin/invoices/generate-from-billing', [
        'client_id' => $client->id,
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    expect(Invoice::query()->where('client_id', $client->id)->firstOrFail()->not_signed_invoice)->not->toBeNull()
        ->and(Storage::disk('public')->allFiles('drive/CATS/invoice/not-signed'))->toHaveCount(1);
});

test('the therapist invoice keeps its own statement format', function () {
    $therapist = therapistUser();
    $invoice = Invoice::factory()->create([
        'therapist_id' => $therapist->id,
        'billed_by' => 'therapist',
        'is_monthly' => true,
        'bill_to_name' => 'Creative Abilities Therapy Services Ltd.',
        'services' => [[
            'name' => 'Home Visit',
            'numberOfSessions' => 2,
            'rate' => '$60.00',
            'rate_numeric' => 60,
            'date' => now()->toDateString(),
            'client' => 'Casey Rivers',
        ]],
    ]);

    $html = view('pdf.therapist-invoice', [
        'invoice' => $invoice,
        'logoPath' => public_path('CatsLogo/web-app-manifest-192x192.png'),
        'therapist' => ['name' => 'Robin Vale', 'email' => 'robin@example.test'],
    ])->render();

    // The statement names the child each line was for, and who it bills.
    expect($html)->toContain('Client')
        ->and($html)->toContain('Casey Rivers')
        ->and($html)->toContain('Home Visit')
        ->and($html)->toContain('Robin Vale')
        ->and($html)->toContain('Creative Abilities Therapy Services Ltd.')
        // None of the family-facing document's parts belong on it.
        ->and($html)->not->toContain('PARENT')
        ->and($html)->not->toContain('CLIENT:');
});

test('a therapist opens the filed document for their own invoice', function () {
    Mail::fake();
    Storage::fake('public');

    $therapist = therapistUser();
    BillingItem::factory()->create(['therapist_id' => $therapist->id]);

    $this->actingAs($therapist)->post('/therapist/invoices/generate-from-billing', [
        'date_start' => now()->subWeek()->toDateString(),
        'date_end' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::query()->where('therapist_id', $therapist->id)->firstOrFail();

    $response = $this->actingAs($therapist)->get("/therapist/invoices/{$invoice->id}/pdf");

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    // The bytes are the file that was filed, not a fresh render.
    $filed = Storage::disk('public')->allFiles('drive/CATS/invoice/not-signed');
    expect($response->getContent())->toBe(Storage::disk('public')->get($filed[0]));
});

test('an invoice with nothing on file is still rendered on request', function () {
    $therapist = therapistUser();
    $invoice = Invoice::factory()->create([
        'therapist_id' => $therapist->id,
        'billed_by' => 'therapist',
        'not_signed_invoice' => null,
    ]);

    $this->actingAs($therapist)->get("/therapist/invoices/{$invoice->id}/pdf")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('a therapist cannot open a document that is not theirs', function () {
    $invoice = Invoice::factory()->create([
        'therapist_id' => therapistUser()->id,
        'billed_by' => 'therapist',
    ]);

    $this->actingAs(therapistUser())->get("/therapist/invoices/{$invoice->id}/pdf")->assertNotFound();
});
