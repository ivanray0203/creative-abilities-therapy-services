<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Complaint;
use App\Models\Intake;
use App\Models\IntakeTherapistApproval;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use App\Models\TeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The role guard stops a client reading admin pages. It says nothing about
 * one therapist reading another's caseload, or one family reading another's
 * invoices — those are same-role reads that only the policies catch.
 */
test('a therapist cannot reach another therapist\'s records', function () {
    $owner = therapistUser();
    $intruder = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $owner->id]);

    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $owner->id,
    ]);
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $owner->id,
        'billed_by' => 'therapist',
    ]);
    $intake = Intake::factory()->create();

    $this->actingAs($intruder)->get("/therapist/sessions/{$session->id}/edit")->assertNotFound();
    $this->actingAs($intruder)->delete("/therapist/sessions/{$session->id}")->assertNotFound();
    $this->actingAs($intruder)->post("/therapist/sessions/{$session->id}/cancel", ['cancel_reason' => 'x'])->assertNotFound();
    $this->actingAs($intruder)->post("/therapist/sessions/{$session->id}/start")->assertNotFound();
    $this->actingAs($intruder)->post("/therapist/sessions/{$session->id}/end")->assertNotFound();

    $this->actingAs($intruder)->get("/therapist/invoices/{$invoice->id}")->assertNotFound();
    $this->actingAs($intruder)->get("/therapist/invoices/{$invoice->id}/edit")->assertNotFound();
    $this->actingAs($intruder)->post("/therapist/invoices/{$invoice->id}/resend")->assertNotFound();

    // An intake nobody sent them is not theirs to read.
    $this->actingAs($intruder)->get("/therapist/intake/{$intake->id}")->assertNotFound();

    // Nor may they decide on a review assigned to someone else.
    $review = IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $owner->id,
        'service' => 'Physiotherapy',
        'status' => 'pending',
    ]);

    $this->actingAs($intruder)
        ->post("/therapist/intake/{$intake->id}/therapist-approve", ['service' => 'Physiotherapy'])
        ->assertForbidden();
    $this->actingAs($intruder)
        ->post("/therapist/intake/{$intake->id}/therapist-reject", ['service' => 'Physiotherapy', 'notes' => 'x'])
        ->assertForbidden();

    expect($review->fresh()->status)->toBe('pending');

    // The caseload list is scoped, so the other therapist's client is absent.
    $this->actingAs($intruder)->get('/therapist/clients')
        ->assertInertia(fn ($page) => $page->has('clients.data', 0));

    expect($session->fresh()->status)->not->toBe('cancelled')
        ->and($session->fresh())->not->toBeNull();
});

test('a parent cannot reach another family\'s records', function () {
    $mine = clientWithUser();
    $theirs = clientWithUser();

    $theirInvoice = Invoice::factory()->create([
        'client_id' => $theirs->id,
        'billed_by' => 'admin',
    ]);
    $theirSession = ScheduleSession::factory()->create([
        'client_id' => $theirs->id,
        'status' => 'pending',
    ]);

    $this->actingAs($mine->user)->get("/client/invoices/{$theirInvoice->id}")->assertNotFound();
    $this->actingAs($mine->user)->post("/client/sessions/{$theirSession->id}/verify")->assertNotFound();
    $this->actingAs($mine->user)->post("/client/sessions/{$theirSession->id}/dispute", ['dispute_reason' => 'x'])
        ->assertNotFound();

    // Switching the portal to someone else's child must not work either.
    $this->actingAs($mine->user)->post('/client/select-child', ['client_id' => $theirs->id])
        ->assertNotFound();

    expect($theirSession->fresh()->status)->toBe('pending');
});

test('a parent only sees their own sessions and invoices in the lists', function () {
    $mine = clientWithUser();
    $theirs = clientWithUser();

    ScheduleSession::factory()->create(['client_id' => $mine->id]);
    ScheduleSession::factory()->count(2)->create(['client_id' => $theirs->id]);
    Invoice::factory()->create(['client_id' => $mine->id, 'billed_by' => 'admin']);
    Invoice::factory()->count(3)->create(['client_id' => $theirs->id, 'billed_by' => 'admin']);

    $this->actingAs($mine->user)->get('/client/calendar')
        ->assertInertia(fn ($page) => $page->has('sessions', 1));

    $this->actingAs($mine->user)->get('/client/invoices')
        ->assertInertia(fn ($page) => $page->has('invoices.data', 1));
});

test('the session lookup endpoints are scoped to the caller, not just their role', function () {
    $owner = therapistUser();
    $stranger = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $owner->id]);
    $clientService = ClientService::factory()->for($client)->create(['therapist_id' => $owner->id]);

    ScheduleSession::factory()->linkedTo($clientService)->create([
        'client_id' => $client->id,
        'therapist_id' => $owner->id,
        'service_id' => $clientService->service_id,
    ]);

    // These take an id from the query string, so the role guard alone let any
    // therapist read another's caseload and any parent another family's diary.
    $this->actingAs($stranger)
        ->getJson("/therapist/sessions/by-user?user_id={$client->id}&role=client")
        ->assertNotFound();
    $this->actingAs($stranger)
        ->getJson("/therapist/sessions/by-user?user_id={$owner->id}&role=therapist")
        ->assertNotFound();
    $this->actingAs($stranger)
        ->getJson("/therapist/sessions/by-client-service/{$clientService->id}")
        ->assertNotFound();
    $this->actingAs($stranger)
        ->getJson("/therapist/sessions/by-user-and-service?user_id={$client->id}&service_id={$clientService->service_id}")
        ->assertNotFound();

    $otherFamily = clientWithUser();
    $this->actingAs($otherFamily->user)
        ->getJson("/client/sessions/by-user?user_id={$client->id}&role=client")
        ->assertNotFound();

    // The people who should see it still do.
    $this->actingAs($owner)
        ->getJson("/therapist/sessions/by-user?user_id={$client->id}&role=client")
        ->assertOk()
        ->assertJsonCount(1);
    $this->actingAs($owner)
        ->getJson("/therapist/sessions/by-user?user_id={$owner->id}&role=therapist")
        ->assertOk()
        ->assertJsonCount(1);
    $this->actingAs($owner)
        ->getJson("/therapist/sessions/by-client-service/{$clientService->id}")
        ->assertOk()
        ->assertJsonCount(1);
    $this->actingAs(adminUser())
        ->getJson("/admin/sessions/by-user?user_id={$client->id}&role=client")
        ->assertOk()
        ->assertJsonCount(1);
});

test('a parent can read their own child diary through the lookup endpoint', function () {
    $family = clientWithUser();
    ScheduleSession::factory()->create(['client_id' => $family->id]);

    $this->actingAs($family->user)
        ->getJson("/client/sessions/by-user?user_id={$family->id}&role=client")
        ->assertOk()
        ->assertJsonCount(1);
});

test('a therapist cannot edit a team member profile that is not their own', function () {
    $owner = therapistUser();
    $intruder = therapistUser();
    TeamMember::factory()->create(['user_id' => $owner->id]);
    $intruderProfile = TeamMember::factory()->create(['user_id' => $intruder->id]);

    // The therapist profile route always resolves the caller's own record,
    // so there is no id to tamper with.
    $this->actingAs($intruder)->get('/therapist/profile')
        ->assertInertia(fn ($page) => $page->where('teamMember.id', $intruderProfile->id));
});

test('a client cannot file a complaint against a session that is not theirs', function () {
    $mine = clientWithUser();
    $theirs = clientWithUser();
    $theirSession = ScheduleSession::factory()->create(['client_id' => $theirs->id]);

    $this->actingAs($mine->user)->post('/client/complaints', [
        'subject' => 'Not mine',
        'description' => 'Attempting to file against another family\'s session.',
        'type' => 'complaints',
        'session_id' => $theirSession->id,
    ]);

    expect(Complaint::count())->toBe(0);
});

test('a valid-looking payload from an intruder still cannot rewrite records it does not own', function () {
    $owner = therapistUser();
    $intruder = therapistUser();
    $ownersClient = Client::factory()->create(['primary_therapist_id' => $owner->id]);
    $intrudersClient = Client::factory()->create(['primary_therapist_id' => $intruder->id]);
    ClientService::factory()->for($intrudersClient)->create(['therapist_id' => $intruder->id]);

    $session = ScheduleSession::factory()->create([
        'client_id' => $ownersClient->id,
        'therapist_id' => $owner->id,
        'scheduled_start' => now()->addDays(30)->setTime(9, 0),
        'scheduled_end' => now()->addDays(30)->setTime(10, 0),
    ]);
    $invoice = Invoice::factory()->create([
        'client_id' => $ownersClient->id,
        'therapist_id' => $owner->id,
        'billed_by' => 'therapist',
        'notes' => 'original',
    ]);

    // Form requests run before the ownership check, so an invalid body is
    // turned back by validation. These payloads are valid — only the
    // ownership guard can stop them.
    $this->actingAs($intruder)->put("/therapist/sessions/{$session->id}", [
        'client_id' => $intrudersClient->id,
        'date' => now()->addDays(60)->toDateString(),
        'start_time' => '11:00',
        'duration' => 60,
    ])->assertNotFound();

    $this->actingAs($intruder)->put("/therapist/invoices/{$invoice->id}", [
        'client_id' => $intrudersClient->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'draft',
        'notes' => 'HIJACKED',
        'services' => [['name' => 'Therapy', 'numberOfSessions' => 1, 'rate_numeric' => 10]],
    ])->assertNotFound();

    expect($session->fresh()->client_id)->toBe($ownersClient->id)
        ->and($session->fresh()->therapist_id)->toBe($owner->id)
        ->and($invoice->fresh()->notes)->toBe('original')
        ->and($invoice->fresh()->client_id)->toBe($ownersClient->id);
});
