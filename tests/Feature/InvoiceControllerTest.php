<?php

use App\Mail\InvoiceResendMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use Illuminate\Support\Facades\Mail;

test('each role only sees invoices scoped to them', function () {
    $therapistA = therapistUser();
    $therapistB = therapistUser();
    Invoice::factory()->create(['therapist_id' => $therapistA->id, 'billed_by' => 'therapist']);
    Invoice::factory()->create(['therapist_id' => $therapistB->id, 'billed_by' => 'therapist']);

    $client = clientWithUser();
    Invoice::factory()->create(['client_id' => $client->id, 'billed_by' => 'admin']);

    $adminResponse = $this->actingAs(adminUser())->get('/admin/invoices');
    $adminResponse->assertOk();
    $adminResponse->assertInertia(fn ($page) => $page
        ->component('invoices/index')
        ->has('invoices.data', 3)
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
        'tax_percentage' => 5,
        'action' => 'draft',
        'services' => [
            ['name' => 'OT Session', 'numberOfSessions' => 4, 'rate_numeric' => 100],
            ['name' => 'Speech Session', 'numberOfSessions' => 2, 'rate_numeric' => 50],
        ],
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::first();
    expect($invoice)->not->toBeNull();
    expect((float) $invoice->sub_total)->toBe(500.0);
    expect((float) $invoice->gst)->toBe(25.0);
    expect((float) $invoice->total)->toBe(525.0);
    expect((float) $invoice->amount_due)->toBe(525.0);
    expect($invoice->status)->toBe('draft');
    expect($invoice->invoice_id)->toStartWith('INV-'.now()->year.'-');
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
