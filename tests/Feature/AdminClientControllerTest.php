<?php

use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\ClientService;
use App\Models\Intake;
use App\Models\IntakeTherapistApproval;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('the client index shows stats, funding overview, and filtered results', function () {
    $fscdIntake = Intake::factory()->create(['funding_source' => 'BDS-FSCD']);
    $privateIntake = Intake::factory()->create(['funding_source' => 'private', 'child_first_name' => 'Zoe']);

    Client::factory()->for($fscdIntake, 'originalIntake')->create(['status' => 'active']);
    Client::factory()->for($privateIntake, 'originalIntake')->create(['status' => 'paused']);

    $response = $this->actingAs(adminUser())->get('/admin/clients');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/clients/index')
        ->where('stats.total', 2)
        ->where('stats.active', 1)
        ->where('stats.paused', 1)
        ->where('stats.fscd', 1)
        ->where('stats.private', 1)
    );

    $filtered = $this->actingAs(adminUser())->get('/admin/clients?search=Zoe');
    $filtered->assertInertia(fn ($page) => $page->has('clients.data', 1));
});

test('the client show page loads scoped relations', function () {
    $intake = Intake::factory()->create();
    $client = Client::factory()->for($intake, 'originalIntake')->create();
    $therapist = therapistUser();
    ClientService::factory()->for($client)->create();

    $response = $this->actingAs(adminUser())->get("/admin/clients/{$client->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/clients/show')
        ->where('client.id', $client->id)
        ->has('client.client_services', 1)
        ->has('therapists')
    );

    expect($therapist)->not->toBeNull();
});

test('an admin can update client status and contract dates', function () {
    $client = Client::factory()->create(['status' => 'active']);

    $this->actingAs(adminUser())->put("/admin/clients/{$client->id}", [
        'status' => 'paused',
        'contract_start_date' => now()->toDateString(),
        'contract_end_date' => now()->addYear()->toDateString(),
    ])->assertSessionHasNoErrors();

    $client->refresh();
    expect($client->status)->toBe('paused');
    expect($client->contract_start_date->toDateString())->toBe(now()->toDateString());
});

test('an admin can delete a client', function () {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())->delete("/admin/clients/{$client->id}")->assertSessionHasNoErrors();

    expect(Client::find($client->id))->toBeNull();
});

test('assigning a therapist for the first time sets primary and assigned therapist without a timeline entry', function () {
    $client = Client::factory()->create(['timeline' => []]);
    $therapist = therapistUser();

    $this->actingAs(adminUser())->post("/admin/clients/{$client->id}/assign-therapist", [
        'therapist_id' => $therapist->id,
    ])->assertSessionHasNoErrors();

    $client->refresh();
    expect($client->primary_therapist_id)->toBe($therapist->id);
    expect($client->assigned_therapist_id)->toBe($therapist->id);
    expect($client->timeline ?? [])->toBeEmpty();
    expect($client->careTeam->pluck('id'))->toContain($therapist->id);
});

test('reassigning the primary therapist writes a timeline entry and updates the care team', function () {
    $original = therapistUser();
    $client = Client::factory()->create([
        'primary_therapist_id' => $original->id,
        'assigned_therapist_id' => $original->id,
        'timeline' => [],
    ]);
    $newTherapist = therapistUser();

    $this->actingAs(adminUser())->post("/admin/clients/{$client->id}/reassign-therapist", [
        'therapist_id' => $newTherapist->id,
    ])->assertSessionHasNoErrors();

    $client->refresh();
    expect($client->primary_therapist_id)->toBe($newTherapist->id);
    expect($client->timeline[0]['action'])->toBe('therapist_reassigned');
    expect($client->timeline[0]['previous_therapist_id'])->toBe($original->id);
    expect($client->timeline[0]['new_therapist_id'])->toBe($newTherapist->id);
    expect($client->careTeam->pluck('id'))->toContain($newTherapist->id);
});

test('an admin can add and remove secondary care-team members but not the primary therapist', function () {
    $primary = therapistUser();
    $client = Client::factory()->create(['primary_therapist_id' => $primary->id]);
    $client->careTeam()->attach($primary->id);
    $secondary = therapistUser();

    $this->actingAs(adminUser())->patch("/admin/clients/{$client->id}/care-team", [
        'therapist_id' => $secondary->id,
        'action' => 'add',
    ])->assertSessionHasNoErrors();

    expect($client->careTeam()->pluck('users.id'))->toContain($secondary->id);

    $this->actingAs(adminUser())->patch("/admin/clients/{$client->id}/care-team", [
        'therapist_id' => $secondary->id,
        'action' => 'remove',
    ])->assertSessionHasNoErrors();

    expect($client->careTeam()->pluck('users.id'))->not->toContain($secondary->id);

    $this->actingAs(adminUser())->patch("/admin/clients/{$client->id}/care-team", [
        'therapist_id' => $primary->id,
        'action' => 'remove',
    ])->assertSessionHasErrors('therapist_id');

    expect($client->careTeam()->pluck('users.id'))->toContain($primary->id);
});

test('an admin can create, update, and delete a client service, refreshing the service_availed cache', function () {
    $client = Client::factory()->create();
    $service = ServiceOffering::factory()->create(['name' => 'Speech Therapy']);
    $therapist = therapistUser();

    $this->actingAs(adminUser())->post("/admin/clients/{$client->id}/services", [
        'service_id' => $service->id,
        'therapist_id' => $therapist->id,
        'frequency' => 'Weekly',
        'duration' => '60 minutes',
        'no_sessions' => 10,
    ])->assertSessionHasNoErrors();

    $clientService = ClientService::where('client_id', $client->id)->first();
    expect($clientService)->not->toBeNull();

    $client->refresh();
    expect($client->service_availed)->toHaveCount(1);
    expect($client->service_availed[0]['service_name'])->toBe('Speech Therapy');

    $this->actingAs(adminUser())->put("/admin/clients/{$client->id}/services/{$clientService->id}", [
        'service_id' => $service->id,
        'frequency' => 'Bi-weekly',
        'no_sessions' => 20,
    ])->assertSessionHasNoErrors();

    $client->refresh();
    expect($client->service_availed[0]['frequency'])->toBe('Bi-weekly');

    $this->actingAs(adminUser())->delete("/admin/clients/{$client->id}/services/{$clientService->id}")
        ->assertSessionHasNoErrors();

    $client->refresh();
    expect(ClientService::find($clientService->id))->toBeNull();
    expect($client->service_availed)->toBe([]);
});

test('an admin can upload and delete a client document', function () {
    Storage::fake('public');

    $client = Client::factory()->create();

    $this->actingAs(adminUser())->post("/admin/clients/{$client->id}/documents", [
        'type' => 'Medical Report',
        'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $document = ClientDocument::where('client_id', $client->id)->first();
    expect($document)->not->toBeNull();
    expect($document->doc_type)->toBe('Medical Report');
    Storage::disk('public')->assertExists($document->drive_file_id);

    $this->actingAs(adminUser())->delete("/admin/clients/documents/{$document->id}")->assertSessionHasNoErrors();

    expect(ClientDocument::find($document->id))->toBeNull();
    Storage::disk('public')->assertMissing($document->drive_file_id);
});

test('an admin can add and delete clinical notes', function () {
    $client = Client::factory()->create(['clinical_notes' => []]);

    $this->actingAs(adminUser())->patch("/admin/clients/{$client->id}/notes", [
        'note' => 'Doing well this week.',
    ])->assertSessionHasNoErrors();

    $client->refresh();
    expect($client->clinical_notes)->toHaveCount(1);
    $noteId = $client->clinical_notes[0]['id'];

    $this->actingAs(adminUser())->delete("/admin/clients/{$client->id}/notes/{$noteId}")
        ->assertSessionHasNoErrors();

    expect($client->refresh()->clinical_notes)->toBe([]);
});

test('an admin can export a client profile as a PDF covering every tab', function () {
    $therapist = User::factory()->therapist()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $intake = Intake::factory()->create([
        'child_first_name' => 'Angel',
        'child_last_name' => 'Diano',
        'funding_source' => 'BDS-FSCD',
        'funding_source_info' => ['FSCD_case_worker_name' => 'Casey Worker'],
        'services_needed' => ['Physiotherapy', 'Counselling'],
    ]);
    $client = Client::factory()->create([
        'original_intake_id' => $intake->id,
        'primary_therapist_id' => $therapist->id,
        'assigned_therapist_id' => $therapist->id,
        'clinical_notes' => [
            ['id' => 'note-1', 'user' => 'Demo Admin', 'date' => '2026-08-01', 'time' => '9:00 AM', 'note' => 'Settled in well.'],
        ],
    ]);
    $client->careTeam()->attach($therapist->id);

    $service = ServiceOffering::factory()->create(['name' => 'Physiotherapy']);
    $clientService = ClientService::factory()->for($client)->create([
        'service_id' => $service->id,
        'therapist_id' => $therapist->id,
    ]);
    ScheduleSession::factory()->linkedTo($clientService)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'completed',
    ]);

    $response = $this->actingAs(adminUser())->get("/admin/clients/{$client->id}/pdf");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $response->assertDownload('angel-diano-profile.pdf');
    expect($response->streamedContent())->toStartWith('%PDF');
});

test('a therapist cannot export a client profile', function () {
    $client = Client::factory()->create();

    $this->actingAs(therapistUser())->get("/admin/clients/{$client->id}/pdf")
        ->assertRedirect('/therapist');
});

test('the client page marks services a therapist declined', function () {
    $decliner = User::factory()->therapist()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $intake = Intake::factory()->create(['services_needed' => ['Physiotherapy', 'Counselling']]);
    $client = Client::factory()->create(['original_intake_id' => $intake->id]);

    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $decliner->id,
        'service' => 'Counselling',
        'status' => 'rejected',
        'notes' => 'Caseload is full.',
        'decided_at' => now(),
    ]);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $decliner->id,
        'service' => 'Physiotherapy',
        'status' => 'approved',
    ]);

    $this->actingAs(adminUser())->get("/admin/clients/{$client->id}")
        ->assertInertia(fn ($page) => $page
            ->has('declinedServices', 1)
            ->where('declinedServices.0.service', 'Counselling')
            ->where('declinedServices.0.therapist', 'Jane Doe')
            // The picker drops the decliner by id, so it has to travel too.
            ->where('declinedServices.0.therapist_id', $decliner->id)
            ->where('declinedServices.0.notes', 'Caseload is full.')
        );
});

test('reassigning a declined service sends it back out and adds it to the existing client on approval', function () {
    $decliner = therapistUser();
    $replacement = therapistUser();
    $service = ServiceOffering::factory()->create(['name' => 'Counselling']);

    $intake = Intake::factory()->create([
        'services_needed' => ['Counselling'],
        'approved_as_client' => true,
    ]);
    $client = Client::factory()->create(['original_intake_id' => $intake->id]);
    $intake->forceFill(['linked_client_id' => $client->id])->save();

    $review = IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $decliner->id,
        'service' => 'Counselling',
        'status' => 'rejected',
    ]);

    $this->actingAs(adminUser())->post("/admin/intake/{$intake->id}/send-to-therapist", [
        'service' => 'Counselling',
        'therapist_id' => $replacement->id,
    ])->assertSessionHasNoErrors();

    expect($review->fresh()->status)->toBe('reassign')
        ->and($review->fresh()->therapist_id)->toBe($replacement->id);

    $this->actingAs($replacement)->post("/therapist/intake/{$intake->id}/therapist-approve", [
        'service' => 'Counselling',
    ])->assertSessionHasNoErrors();

    // Attached to the client that already exists — no second promotion.
    expect(Client::count())->toBe(1)
        ->and($client->fresh()->clientServices->pluck('service_id')->all())->toBe([$service->id])
        ->and($client->fresh()->careTeam->pluck('id'))->toContain($replacement->id);

    $this->actingAs(adminUser())->get("/admin/clients/{$client->id}")
        ->assertInertia(fn ($page) => $page->has('declinedServices', 0));
});

test('the progress tab counts delivery per service without double-counting shared visits', function () {
    $therapist = User::factory()->therapist()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $client = Client::factory()->create();

    $speech = ClientService::factory()->for($client)->create([
        'service_id' => ServiceOffering::factory()->create(['name' => 'Speech'])->id,
        'therapist_id' => $therapist->id,
        'no_sessions' => 4,
        'goals' => 'Two-word phrases.',
        'frequency' => 'Weekly',
    ]);
    $physio = ClientService::factory()->for($client)->create([
        'service_id' => ServiceOffering::factory()->create(['name' => 'Physio'])->id,
        'therapist_id' => $therapist->id,
        'no_sessions' => 0,
    ]);

    // One visit covering both services: counts once for each, once overall.
    $shared = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'completed',
        'elapsed_time' => '01:30:00',
        'scheduled_start' => now(),
    ]);
    $shared->clientServices()->sync([$speech->id, $physio->id]);

    // Speech only, an hour, in a past month.
    $speechOnly = ScheduleSession::factory()->linkedTo($speech)->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'confirmed',
        'elapsed_time' => '01:00:00',
        'scheduled_start' => now()->subMonths(2),
    ]);

    // Neither delivered: one missed, one cancelled, one still booked.
    ScheduleSession::factory()->create(['client_id' => $client->id, 'status' => 'no_show']);
    ScheduleSession::factory()->create(['client_id' => $client->id, 'status' => 'cancelled']);
    ScheduleSession::factory()->create(['client_id' => $client->id, 'status' => 'scheduled']);

    $this->actingAs(adminUser())->get("/admin/clients/{$client->id}")
        ->assertInertia(fn ($page) => $page
            ->has('progress.services', 2)
            // Speech: 2 of 4 delivered, 2.5 hours, goals carried through.
            ->where('progress.services.0.name', 'Speech')
            ->where('progress.services.0.delivered', 2)
            ->where('progress.services.0.authorised', 4)
            ->where('progress.services.0.remaining', 2)
            ->where('progress.services.0.percent', 50)
            ->where('progress.services.0.hours', 2.5)
            ->where('progress.services.0.therapist', 'Jane Doe')
            ->where('progress.services.0.goals', 'Two-word phrases.')
            // Physio authorised nothing, so there is no bar to draw.
            ->where('progress.services.1.delivered', 1)
            ->where('progress.services.1.authorised', null)
            ->where('progress.services.1.percent', null)
            // The shared visit is one visit, not two, in the totals.
            ->where('progress.hours.total', 2.5)
            ->where('progress.hours.this_month', 1.5)
            ->where('progress.attendance.attended', 2)
            ->where('progress.attendance.cancelled', 1)
            ->where('progress.attendance.no_show', 1)
            // 2 attended of 3 kept-or-missed; the cancellation is excluded.
            ->where('progress.attendance.rate', 67)
        );

    expect($speechOnly->fresh()->status)->toBe('confirmed');
});

test('the progress tab reports nothing to show for a client with no services or sessions', function () {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())->get("/admin/clients/{$client->id}")
        ->assertInertia(fn ($page) => $page
            ->where('progress.has_data', false)
            ->where('progress.attendance.rate', null)
            ->where('progress.hours.total', 0)
        );
});
