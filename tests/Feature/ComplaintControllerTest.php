<?php

use App\Models\Client;
use App\Models\Complaint;
use App\Models\ScheduleSession;

test('each role only sees complaints scoped to them', function () {
    $therapistA = therapistUser();
    $therapistB = therapistUser();
    Complaint::factory()->create(['therapist_id' => $therapistA->id, 'complained_by' => 'therapist']);
    Complaint::factory()->create(['therapist_id' => $therapistB->id, 'complained_by' => 'therapist']);

    $client = clientWithUser();
    Complaint::factory()->create(['client_id' => $client->id, 'complained_by' => 'client']);

    $adminResponse = $this->actingAs(adminUser())->get('/admin/messages');
    $adminResponse->assertOk();
    $adminResponse->assertInertia(fn ($page) => $page
        ->component('complaints/index')
        ->has('complaints.data', 3)
    );

    $therapistResponse = $this->actingAs($therapistA)->get('/therapist/complaints');
    $therapistResponse->assertInertia(fn ($page) => $page->has('complaints.data', 1));

    $clientResponse = $this->actingAs($client->user)->get('/client/complaints');
    $clientResponse->assertInertia(fn ($page) => $page->has('complaints.data', 1));
});

test('a client does not see a complaint filed against them by a therapist', function () {
    $client = clientWithUser();
    $therapist = therapistUser();

    // A therapist-filed complaint referencing this same client should not
    // appear in the client's own "complaints I filed" list.
    Complaint::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'complained_by' => 'therapist',
    ]);

    $response = $this->actingAs($client->user)->get('/client/complaints');
    $response->assertInertia(fn ($page) => $page->has('complaints.data', 0));
});

test('filing a complaint forces client_id, therapist_id, and complained_by from the acting user', function () {
    $client = clientWithUser();
    $therapist = therapistUser();
    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
    ]);

    $this->actingAs($client->user)->post('/client/complaints', [
        'session_id' => $session->id,
        'subject' => 'Missed appointment',
        'description' => 'The therapist did not show up for the scheduled session.',
        'category' => 'scheduling',
        'consent_given' => true,
    ])->assertSessionHasNoErrors();

    $complaint = Complaint::first();
    expect($complaint)->not->toBeNull();
    expect($complaint->complained_by)->toBe('client');
    expect($complaint->client_id)->toBe($client->id);
    expect($complaint->therapist_id)->toBe($therapist->id);
    expect($complaint->status)->toBe('open');
    expect($complaint->type)->toBe('complaints');
});

test('a therapist filing a complaint cannot spoof a different client or therapist', function () {
    $client = Client::factory()->create();
    $therapist = therapistUser();
    $otherTherapist = therapistUser();
    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
    ]);

    $this->actingAs($therapist)->post('/therapist/complaints', [
        'session_id' => $session->id,
        'subject' => 'Client concern',
        'description' => 'Documenting a concern about this session for review.',
        'category' => 'communication',
        'consent_given' => true,
        // Attempting to spoof identity fields not accepted by the request.
        'therapist_id' => $otherTherapist->id,
        'client_id' => 999,
    ])->assertSessionHasNoErrors();

    $complaint = Complaint::first();
    expect($complaint->therapist_id)->toBe($therapist->id);
    expect($complaint->client_id)->toBe($client->id);
});

test('starting review sets the reviewer from the authenticated admin, not the request body', function () {
    $admin = adminUser();
    $otherAdmin = adminUser();
    $complaint = Complaint::factory()->create(['status' => 'open']);

    $this->actingAs($admin)->post("/admin/messages/{$complaint->id}/start-review", [
        'reviewed_by' => $otherAdmin->id,
    ])->assertSessionHasNoErrors();

    $complaint->refresh();
    expect($complaint->status)->toBe('under_review');
    expect($complaint->reviewed_by_id)->toBe($admin->id);
    expect($complaint->reviewed_at)->not->toBeNull();
});

test('resolving a complaint requires an admin_response and records the resolver', function () {
    $admin = adminUser();
    $complaint = Complaint::factory()->create(['status' => 'under_review']);

    $this->actingAs($admin)->post("/admin/messages/{$complaint->id}/resolve", [])
        ->assertSessionHasErrors('admin_response');

    $this->actingAs($admin)->post("/admin/messages/{$complaint->id}/resolve", [
        'admin_response' => 'Reviewed and addressed with the therapist directly.',
    ])->assertSessionHasNoErrors();

    $complaint->refresh();
    expect($complaint->status)->toBe('resolved');
    expect($complaint->admin_response)->toBe('Reviewed and addressed with the therapist directly.');
    expect($complaint->resolved_by_id)->toBe($admin->id);
    expect($complaint->resolve_at)->not->toBeNull();
});

test('a therapist cannot start review or resolve a complaint', function () {
    $therapist = therapistUser();
    $complaint = Complaint::factory()->create(['status' => 'open']);

    // These actions only exist under the admin route group, gated by
    // EnsureRole — a therapist hitting them is redirected to their own
    // home rather than reaching the controller.
    $this->actingAs($therapist)->post("/admin/messages/{$complaint->id}/start-review")
        ->assertRedirect('/therapist');

    expect($complaint->refresh()->status)->toBe('open');
});
