<?php

use App\Mail\InvoiceResendMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use Illuminate\Support\Facades\Mail;

test('each role only sees invoices scoped to them', function () {
    $therapistA = therapistUser();
    $therapistB = therapistUser();
    // Per-client bills: the therapist's own working record, not the admin's.
    Invoice::factory()->create(['therapist_id' => $therapistA->id, 'billed_by' => 'therapist']);
    Invoice::factory()->create(['therapist_id' => $therapistB->id, 'billed_by' => 'therapist']);

    $client = clientWithUser();
    Invoice::factory()->create(['client_id' => $client->id, 'billed_by' => 'admin']);

    // Only the clinic's own invoice reaches the admin list; a therapist's
    // client bills wait for the month-end statement.
    $adminResponse = $this->actingAs(adminUser())->get('/admin/invoices');
    $adminResponse->assertOk();
    $adminResponse->assertInertia(fn ($page) => $page
        ->component('invoices/index')
        ->has('invoices.data', 1)
    );

    $therapistResponse = $this->actingAs($therapistA)->get('/therapist/invoices');
    $therapistResponse->assertInertia(fn ($page) => $page->has('invoices.data', 1));

    $clientResponse = $this->actingAs($client->user)->get('/client/invoices');
    $clientResponse->assertInertia(fn ($page) => $page->has('invoices.data', 1));
});

test('a client cannot view another client\'s invoice', function () {
    $client = clientWithUser();
    $otherInvoice = Invoice::factory()->create();

    $this->actingAs($client->user)->get("/client/invoices/{$otherInvoice->id}")->assertNotFound();
});

test('creating an invoice computes totals from the line items using the documented formula', function () {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())->post('/admin/invoices', [
        'client_id' => $client->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'draft',
        'services' => [
            ['name' => 'OT Session', 'numberOfSessions' => 4, 'rate_numeric' => 100],
            ['name' => 'Speech Session', 'numberOfSessions' => 2, 'rate_numeric' => 50],
        ],
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::first();
    expect($invoice)->not->toBeNull();
    expect((float) $invoice->sub_total)->toBe(500.0);
    // Invoices are raised without GST, so the total is the sub total.
    expect((float) $invoice->tax_percentage)->toBe(0.0);
    expect((float) $invoice->gst)->toBe(0.0);
    expect((float) $invoice->total)->toBe(500.0);
    expect((float) $invoice->amount_due)->toBe(500.0);
    expect($invoice->status)->toBe('draft');
    expect($invoice->invoice_id)->toStartWith('INV-'.now()->year.'-');
});

test('a posted tax_percentage cannot put gst back onto a new invoice', function () {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())->post('/admin/invoices', [
        'client_id' => $client->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'tax_percentage' => 5,
        'action' => 'draft',
        'services' => [
            ['name' => 'OT Session', 'numberOfSessions' => 1, 'rate_numeric' => 100],
        ],
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::first();
    expect((float) $invoice->tax_percentage)->toBe(0.0);
    expect((float) $invoice->total)->toBe(100.0);
});

test('an invoice raised before gst was dropped keeps its rate through an edit', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'tax_percentage' => 5.00,
        'status' => 'draft',
    ]);

    $this->actingAs(adminUser())->put("/admin/invoices/{$invoice->id}", [
        'client_id' => $client->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'draft',
        'services' => [
            ['name' => 'OT Session', 'numberOfSessions' => 1, 'rate_numeric' => 100],
        ],
    ])->assertSessionHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->tax_percentage)->toBe(5.0);
    expect((float) $invoice->gst)->toBe(5.0);
    expect((float) $invoice->total)->toBe(105.0);
});

test('save and send sets the invoice status to sent and emails the client', function () {
    Mail::fake();

    $client = clientWithUser();

    $this->actingAs(adminUser())->post('/admin/invoices', [
        'client_id' => $client->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'send',
        'services' => [
            ['name' => 'OT Session', 'numberOfSessions' => 1, 'rate_numeric' => 100],
        ],
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::first();
    expect($invoice->status)->toBe('sent');
    Mail::assertQueued(InvoiceResendMail::class, fn ($mail) => $mail->hasTo($client->user->email)
        && $mail->invoice->id === $invoice->id);
});

test('saving an invoice as a draft does not email the client', function () {
    Mail::fake();

    $client = clientWithUser();

    $this->actingAs(adminUser())->post('/admin/invoices', [
        'client_id' => $client->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'draft',
        'services' => [
            ['name' => 'OT Session', 'numberOfSessions' => 1, 'rate_numeric' => 100],
        ],
    ])->assertSessionHasNoErrors();

    Mail::assertNothingQueued();
});

test('editing an invoice with save and send emails the client', function () {
    Mail::fake();

    $client = clientWithUser();
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'status' => 'draft']);

    $this->actingAs(adminUser())->put("/admin/invoices/{$invoice->id}", [
        'client_id' => $client->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'send',
        'services' => [
            ['name' => 'OT Session', 'numberOfSessions' => 1, 'rate_numeric' => 100],
        ],
    ])->assertSessionHasNoErrors();

    expect($invoice->refresh()->status)->toBe('sent');
    Mail::assertQueued(InvoiceResendMail::class, fn ($mail) => $mail->hasTo($client->user->email));
});

test('a therapist creating an invoice is always billed_by therapist as themselves', function () {
    $client = Client::factory()->create();
    $therapist = therapistUser();

    $this->actingAs($therapist)->post('/therapist/invoices', [
        'client_id' => $client->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'draft',
        'services' => [
            ['name' => 'OT Session', 'numberOfSessions' => 1, 'rate_numeric' => 100],
        ],
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::first();
    expect($invoice->billed_by)->toBe('therapist');
    expect($invoice->therapist_id)->toBe($therapist->id);
});

test('a therapist cannot edit another therapist\'s invoice', function () {
    $owner = therapistUser();
    $intruder = therapistUser();
    $invoice = Invoice::factory()->create(['therapist_id' => $owner->id, 'billed_by' => 'therapist']);

    $this->actingAs($intruder)->get("/therapist/invoices/{$invoice->id}/edit")->assertNotFound();
});

test('generating an invoice from a completed session links to an existing opposite-billed_by invoice on that session', function () {
    $client = Client::factory()->create();
    $therapist = therapistUser();
    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'completed',
    ]);

    $this->actingAs($therapist)->post('/therapist/invoices/generate-from-session', [
        'session_id' => $session->id,
    ])->assertSessionHasNoErrors();

    $therapistInvoice = Invoice::where('billed_by', 'therapist')->first();
    expect($therapistInvoice)->not->toBeNull();
    expect($therapistInvoice->session_id)->toBe($session->id);
    expect($therapistInvoice->linked_therapist_invoice_id)->toBeNull();

    $this->actingAs(adminUser())->post('/admin/invoices/generate-from-session', [
        'session_id' => $session->id,
    ])->assertSessionHasNoErrors();

    $adminInvoice = Invoice::where('billed_by', 'admin')->first();
    expect($adminInvoice)->not->toBeNull();
    expect($adminInvoice->linked_therapist_invoice_id)->toBe($therapistInvoice->id);
});

test('generating an invoice from a non-completed session fails', function () {
    $session = ScheduleSession::factory()->create(['status' => 'scheduled']);

    $this->actingAs(adminUser())->post('/admin/invoices/generate-from-session', [
        'session_id' => $session->id,
    ])->assertSessionHasErrors('session_id');

    expect(Invoice::count())->toBe(0);
});

test('manually creating an invoice can link it to a session, and paying it then completes that session', function () {
    $client = Client::factory()->create();
    $session = ScheduleSession::factory()->create(['client_id' => $client->id, 'status' => 'inprogress']);

    $this->actingAs(adminUser())->post('/admin/invoices', [
        'client_id' => $client->id,
        'session_id' => $session->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'send',
        'services' => [
            ['name' => 'OT Session', 'numberOfSessions' => 1, 'rate_numeric' => 100],
        ],
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::first();
    expect($invoice->session_id)->toBe($session->id);

    $this->actingAs(adminUser())->post("/admin/invoices/{$invoice->id}/mark-paid")
        ->assertSessionHasNoErrors();

    expect($session->refresh()->status)->toBe('completed');
});

test('marking an invoice paid also completes its linked session', function () {
    $session = ScheduleSession::factory()->create(['status' => 'inprogress']);
    $invoice = Invoice::factory()->create(['session_id' => $session->id, 'status' => 'sent']);

    $this->actingAs(adminUser())->post("/admin/invoices/{$invoice->id}/mark-paid")
        ->assertSessionHasNoErrors();

    $invoice->refresh();
    expect($invoice->status)->toBe('paid');
    expect($invoice->paid_at)->not->toBeNull();
    expect($session->refresh()->status)->toBe('completed');
});

test('resend is allowed for admin and the owning therapist but not another therapist', function () {
    $owner = therapistUser();
    $intruder = therapistUser();
    $invoice = Invoice::factory()->create(['therapist_id' => $owner->id, 'billed_by' => 'therapist']);

    $this->actingAs($owner)->post("/therapist/invoices/{$invoice->id}/resend")->assertSessionHasNoErrors();
    $this->actingAs($intruder)->post("/therapist/invoices/{$invoice->id}/resend")->assertNotFound();

    expect($invoice->refresh()->timeline)->toHaveCount(1);
});

test('only an admin can delete an invoice', function () {
    $invoice = Invoice::factory()->create();

    $this->actingAs(adminUser())->delete("/admin/invoices/{$invoice->id}")->assertSessionHasNoErrors();

    expect(Invoice::find($invoice->id))->toBeNull();
});

test('the invoice form only offers clients with a session already delivered', function () {
    $therapist = therapistUser();

    $delivered = Client::factory()->create();
    ScheduleSession::factory()->create([
        'client_id' => $delivered->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);

    $bookedOnly = Client::factory()->create();
    ScheduleSession::factory()->create([
        'client_id' => $bookedOnly->id,
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
    ]);

    Client::factory()->create(); // no sessions at all

    foreach (['/admin/invoices/create' => adminUser(), '/therapist/invoices/create' => $therapist] as $url => $user) {
        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('clients', 1)
                ->where('clients.0.id', $delivered->id)
            );
    }
});

test('a therapist only sees clients they themselves have delivered a session to', function () {
    $therapist = therapistUser();
    $otherTherapist = therapistUser();

    $mine = Client::factory()->create();
    ScheduleSession::factory()->create([
        'client_id' => $mine->id,
        'therapist_id' => $therapist->id,
        'status' => 'confirmed',
    ]);

    $theirs = Client::factory()->create();
    ScheduleSession::factory()->create([
        'client_id' => $theirs->id,
        'therapist_id' => $otherTherapist->id,
        'status' => 'completed',
    ]);

    $this->actingAs($therapist)->get('/therapist/invoices/create')
        ->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->where('clients.0.id', $mine->id)
        );

    $this->actingAs(adminUser())->get('/admin/invoices/create')
        ->assertInertia(fn ($page) => $page->has('clients', 2));
});

test('editing an invoice keeps its own client selectable even with no delivered session', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'billed_by' => 'admin']);

    $this->actingAs(adminUser())->get("/admin/invoices/{$invoice->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->where('clients.0.id', $client->id)
        );
});

test('a therapist invoice is billed to the clinic and never reaches the family', function () {
    $therapist = therapistUser();
    $client = clientWithUser();

    $therapistInvoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'billed_by' => 'therapist',
    ]);
    $clientInvoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'billed_by' => 'admin',
    ]);

    // The parent sees only what the clinic billed them.
    $this->actingAs($client->user)->get('/client/invoices')
        ->assertInertia(fn ($page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $clientInvoice->id)
        );
    $this->actingAs($client->user)->get("/client/invoices/{$therapistInvoice->id}")->assertNotFound();
    $this->actingAs($client->user)->get("/client/invoices/{$clientInvoice->id}")->assertOk();

    // The therapist sees only their own bill to the clinic.
    $this->actingAs($therapist)->get('/therapist/invoices')
        ->assertInertia(fn ($page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $therapistInvoice->id)
        );
    $this->actingAs($therapist)->get("/therapist/invoices/{$clientInvoice->id}")->assertNotFound();

    // The admin sees the clinic's own invoice. The therapist's client bill
    // only reaches them once the month closes into a monthly statement.
    $this->actingAs(adminUser())->get('/admin/invoices')
        ->assertInertia(fn ($page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $clientInvoice->id)
        );
    $this->actingAs(adminUser())->get("/admin/invoices/{$therapistInvoice->id}")->assertNotFound();

    $therapistInvoice->update(['is_monthly' => true]);

    $this->actingAs(adminUser())->get('/admin/invoices')
        ->assertInertia(fn ($page) => $page->has('invoices.data', 2));
    $this->actingAs(adminUser())->get("/admin/invoices/{$therapistInvoice->id}")->assertOk();
});

test('a therapist cannot edit or resend the clinic\'s invoice to the family', function () {
    $therapist = therapistUser();
    $client = clientWithUser();

    $clientInvoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'billed_by' => 'admin',
    ]);

    $this->actingAs($therapist)->get("/therapist/invoices/{$clientInvoice->id}/edit")->assertNotFound();
    $this->actingAs($therapist)->post("/therapist/invoices/{$clientInvoice->id}/resend")->assertNotFound();
});

test('a therapist bill is never sent on creation, so nobody is emailed', function () {
    Mail::fake();

    $admin = adminUser();
    $therapist = therapistUser();
    $client = clientWithUser();
    ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'confirmed',
    ]);

    $this->actingAs($therapist)->post('/therapist/invoices', [
        'client_id' => $client->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'send',
        'services' => [[
            'name' => 'Speech Therapy',
            'numberOfSessions' => 1,
            'rate_numeric' => 120,
        ]],
    ])->assertSessionHasNoErrors();

    // Therapists bill the clinic monthly, so a per-client bill stays a draft
    // on their own list — the month-end statement is what reaches the admin.
    expect(Invoice::query()->where('billed_by', 'therapist')->value('status'))->toBe('draft');

    Mail::assertNotQueued(InvoiceResendMail::class, fn ($mail) => $mail->hasTo($admin->email));
    Mail::assertNotQueued(InvoiceResendMail::class, fn ($mail) => $mail->hasTo($client->user->email));
});

test('an admin invoice can be linked to the therapist bill it recovers', function () {
    $therapist = therapistUser();
    $client = clientWithUser();
    ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'confirmed',
    ]);

    $therapistInvoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'billed_by' => 'therapist',
        'status' => 'sent',
    ]);

    $this->actingAs(adminUser())->post('/admin/invoices', [
        'client_id' => $client->id,
        'linked_therapist_invoice_id' => $therapistInvoice->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'draft',
        'services' => [[
            'name' => 'Speech Therapy',
            'numberOfSessions' => 1,
            'rate_numeric' => 150,
        ]],
    ])->assertSessionHasNoErrors();

    $adminInvoice = Invoice::query()->where('billed_by', 'admin')->firstOrFail();
    expect($adminInvoice->linked_therapist_invoice_id)->toBe($therapistInvoice->id);
    expect($therapistInvoice->fresh()->linkedAdminInvoices->pluck('id')->all())->toBe([$adminInvoice->id]);
});

test('an admin invoice cannot recover another client\'s therapist bill', function () {
    $therapist = therapistUser();
    $client = clientWithUser();
    $otherClient = Client::factory()->create();
    ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'confirmed',
    ]);

    $foreignInvoice = Invoice::factory()->create([
        'client_id' => $otherClient->id,
        'therapist_id' => $therapist->id,
        'billed_by' => 'therapist',
    ]);

    $this->actingAs(adminUser())->post('/admin/invoices', [
        'client_id' => $client->id,
        'linked_therapist_invoice_id' => $foreignInvoice->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'draft',
        'services' => [['name' => 'Speech Therapy', 'numberOfSessions' => 1, 'rate_numeric' => 150]],
    ])->assertSessionHasErrors('linked_therapist_invoice_id');
});

test('the invoice form offers only unsettled therapist bills, and the admin list totals what is owed', function () {
    $therapist = therapistUser();
    $client = clientWithUser();
    ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'confirmed',
    ]);

    // Only monthly statements count against what the clinic owes: an
    // unbilled client bill has not been presented to it yet.
    $unpaid = Invoice::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'billed_by' => 'therapist',
        'is_monthly' => true,
        'status' => 'sent',
        'total' => 120,
    ]);
    Invoice::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'billed_by' => 'therapist',
        'is_monthly' => true,
        'status' => 'paid',
        'total' => 300,
    ]);
    Invoice::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'billed_by' => 'therapist',
        'status' => 'draft',
        'total' => 999,
    ]);

    $this->actingAs(adminUser())->get('/admin/invoices/create')
        ->assertInertia(fn ($page) => $page
            ->has('therapistInvoices', 1)
            ->where('therapistInvoices.0.id', $unpaid->id)
        );

    $this->actingAs(adminUser())->get('/admin/invoices')
        ->assertInertia(fn ($page) => $page->where('stats.owed_to_therapists', 120));

    $this->actingAs($therapist)->get('/therapist/invoices')
        ->assertInertia(fn ($page) => $page->where('stats.owed_to_therapists', null));
});

test('the admin invoice list can be filtered to one side of the ledger', function () {
    $therapist = therapistUser();
    $client = clientWithUser();

    // Only a monthly statement is on the admin's side of the ledger.
    $fromTherapist = Invoice::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'billed_by' => 'therapist',
        'is_monthly' => true,
    ]);
    $toClient = Invoice::factory()->create([
        'client_id' => $client->id,
        'billed_by' => 'admin',
    ]);

    $admin = adminUser();

    $this->actingAs($admin)->get('/admin/invoices')
        ->assertInertia(fn ($page) => $page
            ->has('invoices.data', 2)
            ->where('filters.direction', 'all')
        );

    $this->actingAs($admin)->get('/admin/invoices?direction=therapist')
        ->assertInertia(fn ($page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $fromTherapist->id)
        );

    $this->actingAs($admin)->get('/admin/invoices?direction=admin')
        ->assertInertia(fn ($page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $toClient->id)
        );

    // A therapist only ever sees their own side, so a hand-typed direction
    // must not widen what they get back.
    $this->actingAs($therapist)->get('/therapist/invoices?direction=admin')
        ->assertInertia(fn ($page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $fromTherapist->id)
        );
});
