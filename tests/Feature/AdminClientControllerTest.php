<?php

use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\ClientService;
use App\Models\Intake;
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
