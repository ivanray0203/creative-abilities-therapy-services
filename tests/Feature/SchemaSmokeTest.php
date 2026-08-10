<?php

use App\Models\Application;
use App\Models\Career;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Complaint;
use App\Models\ConsentClause;
use App\Models\ConsentDocument;
use App\Models\Intake;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;
use App\Models\SystemLog;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\UserConsentAcceptance;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('intake can be promoted into a client with billing and care team', function () {
    $admin = User::factory()->admin()->create();
    $therapist = User::factory()->therapist()->create();

    $intake = Intake::factory()->create([
        'submitted_by_id' => $admin->id,
    ]);

    $client = Client::factory()->create([
        'original_intake_id' => $intake->id,
    ]);

    $client->assignTherapist($therapist);

    expect($client->fresh()->primary_therapist_id)->toBe($therapist->id)
        ->and($client->careTeam()->pluck('users.id'))->toContain($therapist->id)
        ->and($client->originalIntake->id)->toBe($intake->id);

    $client->reassignPrimaryTherapist(User::factory()->therapist()->create(), $admin);

    expect($client->fresh()->timeline)->not->toBeEmpty();
});

test('client services, sessions, and invoices link together', function () {
    $client = Client::factory()->create();
    $service = ServiceOffering::factory()->create();
    $therapist = User::factory()->therapist()->create();

    $clientService = ClientService::factory()->create([
        'client_id' => $client->id,
        'service_id' => $service->id,
        'therapist_id' => $therapist->id,
    ]);

    $session = ScheduleSession::factory()->linkedTo($clientService)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'service_id' => $service->id,
    ]);

    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'session_id' => $session->id,
        'therapist_id' => $therapist->id,
    ]);
    $invoice->calculateTotals();
    $invoice->save();

    expect($invoice->fresh()->total)->toBeGreaterThan(0)
        ->and($session->fresh()->invoices->first()->id)->toBe($invoice->id)
        ->and($clientService->fresh()->sessions->first()->id)->toBe($session->id);
});

test('team member SIN is stored one-way hashed, never plaintext', function () {
    $teamMember = TeamMember::factory()->create([
        'sin_number' => '123456789',
    ]);

    expect($teamMember->fresh()->sin_number)
        ->not->toBe('123456789')
        ->toBe(hash('sha256', '123456789'));
});

test('application links to a career posting and can become hired', function () {
    $career = Career::factory()->create();

    $application = Application::factory()->create([
        'position_id' => $career->id,
    ]);

    expect($application->position->id)->toBe($career->id);

    $application->update(['hired' => true, 'application_status' => 'hired']);

    expect($application->fresh()->hired)->toBeTrue();
});

test('consent document exposes current clauses and records acceptance', function () {
    $document = ConsentDocument::factory()->create([
        'is_active' => true,
        'effective_date' => now()->subDay(),
    ]);

    ConsentClause::factory()->count(2)->create(['document_id' => $document->id]);

    $user = User::factory()->create();
    $acceptance = UserConsentAcceptance::factory()->create([
        'document_id' => $document->id,
        'user_id' => $user->id,
    ]);

    expect($document->is_current)->toBeTrue()
        ->and($document->clauses)->toHaveCount(2);

    $acceptance->revoke();

    expect($acceptance->fresh()->is_revoked)->toBeTrue();
});

test('complaints link a client, therapist, and session', function () {
    $client = Client::factory()->create();
    $session = ScheduleSession::factory()->create(['client_id' => $client->id]);

    $complaint = Complaint::factory()->create([
        'client_id' => $client->id,
        'session_id' => $session->id,
        'therapist_id' => $session->therapist_id,
    ]);

    expect($complaint->client->id)->toBe($client->id)
        ->and($complaint->session->id)->toBe($session->id);
});

test('system log stores structured details', function () {
    $log = SystemLog::factory()->create();

    expect($log->details)->toBeArray();
});

test('reference number generator produces sequential, year-scoped numbers', function () {
    $generator = new ReferenceNumberGenerator;

    $first = $generator->intake();
    Intake::factory()->create(['reference_number' => $first]);

    $second = $generator->intake();

    expect($first)->toStartWith('INT-'.now()->year.'-')
        ->and($second)->not->toBe($first);
});
