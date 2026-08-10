<?php

use App\Mail\WelcomeClientAccountMail;
use App\Models\BillingAccount;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Intake;
use App\Models\IntakeDocument;
use App\Models\IntakeTherapistApproval;
use App\Models\IntakeTherapistApprovalHistory;
use App\Models\ServiceOffering;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validAdminIntakePayload(array $overrides = []): array
{
    return array_merge([
        'child_first_name' => 'John',
        'child_last_name' => 'Doe',
        'date_of_birth' => now()->subYears(6)->toDateString(),
        'gender' => 'male',
        'street_address' => '123 Main St',
        'city' => 'Calgary',
        'state_province' => 'Alberta',
        'postal_code' => 'T2N 1N4',
        'grade_level' => 'Grade 1',
        'school_name' => 'Sample School',
        'services_needed' => ['Speech and Language Therapy'],
        'currently_receiving_services' => false,
        'diagnosis' => ['Autism Spectrum Disorder (ASD)'],
        'has_medical_conditions' => false,
        'languages_spoken_at_home' => 'English',
        'require_interpreter' => false,
        'funding_source' => 'private',
        'available_days' => ['Monday', 'Tuesday'],
        'preferred_times' => ['Mornings (8am-11am)'],
        'primary_parent_name' => 'Jane Doe',
        'primary_parent_phone' => '5874338780',
        'primary_parent_email' => 'jane@example.com',
        'primary_relationship_to_child' => 'mother',
        'primary_contact_method' => 'email',
        'emergency_contact_name' => 'Jane Doe',
        'emergency_contact_phone' => '5874338780',
        'emergency_contact_relationship' => 'mother',
        'referral_source' => 'Online Search (Google, etc.)',
    ], $overrides);
}

test('the intake list excludes promoted intakes and exposes stats', function () {
    Intake::factory()->count(3)->create(['status' => 'pending', 'funding_source' => 'private']);
    Intake::factory()->create(['status' => 'under_review', 'funding_source' => 'BDS-FSCD']);
    Intake::factory()->create(['approved_as_client' => true]);

    $response = $this->actingAs(adminUser())->get('/admin/intake');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/intake/index')
        ->where('stats.pending', 3)
        ->where('stats.under_review', 1)
        ->where('stats.fscd', 1)
        ->where('stats.private', 3)
        ->has('intakes.data', 4)
    );
});

test('the intake list can be searched and filtered by funding type', function () {
    Intake::factory()->create(['child_first_name' => 'Zoe', 'funding_source' => 'private']);
    Intake::factory()->create(['child_first_name' => 'Milo', 'funding_source' => 'SS-FSCD']);

    $admin = adminUser();

    $this->actingAs($admin)->get('/admin/intake?search=Zoe')
        ->assertInertia(fn ($page) => $page->has('intakes.data', 1)->where('intakes.data.0.child_first_name', 'Zoe'));

    $this->actingAs($admin)->get('/admin/intake?funding=fscd')
        ->assertInertia(fn ($page) => $page->has('intakes.data', 1)->where('intakes.data.0.child_first_name', 'Milo'));
});

test('the list badge overlays a pending therapist review on top of the intake status', function () {
    $intake = Intake::factory()->create(['status' => 'approved']);
    IntakeTherapistApproval::factory()->create(['intake_id' => $intake->id, 'status' => 'pending']);

    $this->actingAs(adminUser())->get('/admin/intake')
        ->assertInertia(fn ($page) => $page
            ->where('intakes.data.0.status_label', 'Awaiting Approval from Therapist')
            ->where('intakes.data.0.status_variant', 'therapist_pending')
        );
});

test('an admin can create an intake', function () {
    $response = $this->actingAs(adminUser())->post('/admin/intake', validAdminIntakePayload());

    $response->assertSessionHasNoErrors();

    expect(Intake::count())->toBe(1);

    $intake = Intake::first();
    expect($intake->status)->toBe('pending');
    expect($intake->reference_number)->toMatch('/^INT-'.now()->year.'-\d{3}$/');
    expect($intake->timeline[0]['title'])->toBe('Intake form submitted via website');
    $response->assertRedirect("/admin/intake/{$intake->id}");
});

test('an admin can update an intake and a timeline entry is appended', function () {
    $intake = Intake::factory()->create(['timeline' => []]);

    $response = $this->actingAs(adminUser())
        ->put("/admin/intake/{$intake->id}", validAdminIntakePayload(['child_first_name' => 'Renamed']));

    $response->assertSessionHasNoErrors();

    $intake->refresh();
    expect($intake->child_first_name)->toBe('Renamed');
    expect($intake->timeline)->toHaveCount(1);
    expect($intake->timeline[0]['title'])->toBe('Intake Edited via Admin');
});

test('the primary parent email cannot be changed once it is set', function () {
    $intake = Intake::factory()->create(['primary_parent_email' => 'original@example.com']);

    $this->actingAs(adminUser())
        ->put("/admin/intake/{$intake->id}", validAdminIntakePayload(['primary_parent_email' => 'hacker@example.com']))
        ->assertSessionHasNoErrors();

    expect($intake->refresh()->primary_parent_email)->toBe('original@example.com');
});

test('an admin can delete an intake', function () {
    $intake = Intake::factory()->create();

    $this->actingAs(adminUser())->delete("/admin/intake/{$intake->id}")->assertRedirect('/admin/intake');

    expect(Intake::count())->toBe(0);
});

test('updating the status appends a timeline entry and an optional note', function () {
    $intake = Intake::factory()->create(['status' => 'pending', 'timeline' => [], 'notes' => []]);

    $this->actingAs(adminUser())
        ->patch("/admin/intake/{$intake->id}/status", ['status' => 'under_review', 'note' => 'Reviewed today'])
        ->assertSessionHasNoErrors();

    $intake->refresh();
    expect($intake->status)->toBe('under_review');
    expect($intake->timeline[0]['title'])->toBe('Updated Status to under_review from pending');
    expect($intake->notes)->toHaveCount(1);
    expect($intake->notes[0]['note'])->toBe('Reviewed today');
});

test('an invalid status transition is rejected', function () {
    $intake = Intake::factory()->create(['status' => 'pending']);

    $this->actingAs(adminUser())
        ->patch("/admin/intake/{$intake->id}/status", [
            'status' => 'approved',
            'therapist_assignments' => [['service' => null, 'therapist_id' => therapistUser()->id]],
        ])
        ->assertSessionHasErrors('status');

    expect($intake->refresh()->status)->toBe('pending');
});

test('moving an intake to approved requires a therapist and creates a therapist review', function () {
    $intake = Intake::factory()->create(['status' => 'under_review']);
    $therapist = therapistUser();

    $this->actingAs(adminUser())
        ->patch("/admin/intake/{$intake->id}/status", ['status' => 'approved'])
        ->assertSessionHasErrors('therapist_assignments');

    $this->actingAs(adminUser())
        ->patch("/admin/intake/{$intake->id}/status", [
            'status' => 'approved',
            'therapist_assignments' => [['service' => null, 'therapist_id' => $therapist->id]],
        ])
        ->assertSessionHasNoErrors();

    $intake->refresh();
    expect($intake->status)->toBe('approved');
    expect($intake->assigned_therapist_id)->toBe($therapist->id);
    expect($intake->approved_as_client)->toBeFalse();
    expect($intake->therapistReviews->first()->status)->toBe('pending');
    expect(IntakeTherapistApprovalHistory::where('status', 'sent')->count())->toBe(1);
});

test('moving a multi-service intake to approved creates one review per service', function () {
    $intake = Intake::factory()->create([
        'status' => 'under_review',
        'services_needed' => ['Occupational Therapy', 'Speech and Language Therapy'],
    ]);
    $otTherapist = therapistUser();
    $slpTherapist = therapistUser();

    $this->actingAs(adminUser())
        ->patch("/admin/intake/{$intake->id}/status", [
            'status' => 'approved',
            'therapist_assignments' => [
                ['service' => 'Occupational Therapy', 'therapist_id' => $otTherapist->id],
                ['service' => 'Speech and Language Therapy', 'therapist_id' => $slpTherapist->id],
            ],
        ])
        ->assertSessionHasNoErrors();

    $reviews = IntakeTherapistApproval::where('intake_id', $intake->id)->get()->keyBy('service');
    expect($reviews)->toHaveCount(2);
    expect($reviews['Occupational Therapy']->therapist_id)->toBe($otTherapist->id);
    expect($reviews['Occupational Therapy']->status)->toBe('pending');
    expect($reviews['Speech and Language Therapy']->therapist_id)->toBe($slpTherapist->id);
    expect(IntakeTherapistApprovalHistory::where('status', 'sent')->count())->toBe(2);
});

test('a service with no available therapist can be left unassigned while another service is assigned', function () {
    $intake = Intake::factory()->create([
        'status' => 'under_review',
        'services_needed' => ['Occupational Therapy', 'Speech and Language Therapy'],
    ]);
    $otTherapist = therapistUser();

    $this->actingAs(adminUser())
        ->patch("/admin/intake/{$intake->id}/status", [
            'status' => 'approved',
            'therapist_assignments' => [
                ['service' => 'Occupational Therapy', 'therapist_id' => $otTherapist->id],
            ],
        ])
        ->assertSessionHasNoErrors();

    $reviews = IntakeTherapistApproval::where('intake_id', $intake->id)->get();
    expect($reviews)->toHaveCount(1);
    expect($reviews->first()->service)->toBe('Occupational Therapy');
});

test('therapist assignments with an unknown service are rejected', function () {
    $intake = Intake::factory()->create([
        'status' => 'under_review',
        'services_needed' => ['Occupational Therapy', 'Speech and Language Therapy'],
    ]);

    $this->actingAs(adminUser())
        ->patch("/admin/intake/{$intake->id}/status", [
            'status' => 'approved',
            'therapist_assignments' => [
                ['service' => 'Physiotherapy', 'therapist_id' => therapistUser()->id],
            ],
        ])
        ->assertSessionHasErrors('therapist_assignments');

    expect(IntakeTherapistApproval::where('intake_id', $intake->id)->count())->toBe(0);
});

test('therapist assignments cannot assign the same service twice', function () {
    $intake = Intake::factory()->create([
        'status' => 'under_review',
        'services_needed' => ['Occupational Therapy', 'Speech and Language Therapy'],
    ]);

    $this->actingAs(adminUser())
        ->patch("/admin/intake/{$intake->id}/status", [
            'status' => 'approved',
            'therapist_assignments' => [
                ['service' => 'Occupational Therapy', 'therapist_id' => therapistUser()->id],
                ['service' => 'Occupational Therapy', 'therapist_id' => therapistUser()->id],
            ],
        ])
        ->assertSessionHasErrors('therapist_assignments');

    expect(IntakeTherapistApproval::where('intake_id', $intake->id)->count())->toBe(0);
});

test('an intake can be sent to a therapist for review', function () {
    $intake = Intake::factory()->create(['status' => 'under_review']);
    $therapist = therapistUser();

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/send-to-therapist", ['therapist_id' => $therapist->id])
        ->assertSessionHasNoErrors();

    $review = IntakeTherapistApproval::where('intake_id', $intake->id)->first();
    expect($review->status)->toBe('pending');
    expect($review->therapist_id)->toBe($therapist->id);

    $history = IntakeTherapistApprovalHistory::where('intake_id', $intake->id)->first();
    expect($history->status)->toBe('sent');
    expect($history->notes)->toBe("Sent to {$therapist->full_name}");
});

test('sending to a therapist is blocked while a review is pending', function () {
    $intake = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create(['intake_id' => $intake->id, 'status' => 'pending']);

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/send-to-therapist", ['therapist_id' => therapistUser()->id])
        ->assertSessionHasErrors('therapist_assignments');

    expect(IntakeTherapistApprovalHistory::count())->toBe(0);
});

test('sending to a therapist is blocked once the review is approved', function () {
    $intake = Intake::factory()->create();
    IntakeTherapistApproval::factory()->create(['intake_id' => $intake->id, 'status' => 'approved']);

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/send-to-therapist", ['therapist_id' => therapistUser()->id])
        ->assertSessionHasErrors('therapist_assignments');
});

test('a rejected review can be re-sent to another therapist as a reassignment', function () {
    $intake = Intake::factory()->create();
    $review = IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'status' => 'rejected',
        'notes' => 'Too full',
        'decided_at' => now(),
    ]);
    $newTherapist = therapistUser();

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/send-to-therapist", ['therapist_id' => $newTherapist->id])
        ->assertSessionHasNoErrors();

    $review->refresh();
    expect($review->status)->toBe('reassign');
    expect($review->therapist_id)->toBe($newTherapist->id);
    expect($review->notes)->toBeNull();
    expect($review->decided_at)->toBeNull();
});

test('a non therapist user cannot be assigned as therapist', function () {
    $intake = Intake::factory()->create();
    $client = User::factory()->client()->create();

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/send-to-therapist", ['therapist_id' => $client->id])
        ->assertSessionHasErrors('therapist_id');
});

test('an admin direct approve promotes the intake to a fully linked client', function () {
    $intake = Intake::factory()->create(['primary_parent_email' => 'parent@example.com', 'primary_parent_name' => 'Maria Dela Cruz']);
    $therapist = therapistUser();

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/approve", ['therapist_id' => $therapist->id])
        ->assertSessionHasNoErrors();

    $intake->refresh();
    expect($intake->approved_as_client)->toBeTrue();
    expect($intake->reviewed)->toBeTrue();
    expect($intake->assigned_therapist_id)->toBe($therapist->id);

    // Regression guard for the Django reference's untupled promote() call:
    // a real Client row must exist and be fully linked.
    expect(Client::count())->toBe(1);

    $client = Client::first();
    expect($client->original_intake_id)->toBe($intake->id);
    expect($client->primary_therapist_id)->toBe($therapist->id);
    expect($client->assigned_therapist_id)->toBe($therapist->id);
    expect((int) $intake->linked_client_id)->toBe($client->id);
    expect($client->careTeam()->pluck('users.id')->all())->toBe([$therapist->id]);
    expect(BillingAccount::where('client_id', $client->id)->count())->toBe(1);

    $parent = User::where('email', 'parent@example.com')->first();
    expect($parent->role)->toBe('client');
    expect($parent->first_name)->toBe('Maria');
    expect($parent->last_name)->toBe('Dela Cruz');
    expect($client->user_id)->toBe($parent->id);
});

test('approving a second intake for the same parent email creates a second client under the same login', function () {
    Mail::fake();

    $firstIntake = Intake::factory()->create(['primary_parent_email' => 'parent@example.com', 'primary_parent_name' => 'Maria Dela Cruz']);
    $firstTherapist = therapistUser();
    $this->actingAs(adminUser())
        ->post("/admin/intake/{$firstIntake->id}/approve", ['therapist_id' => $firstTherapist->id])
        ->assertSessionHasNoErrors();

    $secondIntake = Intake::factory()->create(['primary_parent_email' => 'parent@example.com', 'primary_parent_name' => 'Maria Dela Cruz']);
    $secondTherapist = therapistUser();

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$secondIntake->id}/approve", ['therapist_id' => $secondTherapist->id])
        ->assertSessionHasNoErrors();

    // Each child gets its own client record...
    expect(Client::count())->toBe(2);

    $firstClient = Client::where('original_intake_id', $firstIntake->id)->first();
    $secondClient = Client::where('original_intake_id', $secondIntake->id)->first();

    expect($firstClient->primary_therapist_id)->toBe($firstTherapist->id);
    expect($secondClient->primary_therapist_id)->toBe($secondTherapist->id);
    expect((int) $secondIntake->refresh()->linked_client_id)->toBe($secondClient->id);
    expect($secondIntake->approved_as_client)->toBeTrue();

    // ...with its own care team, rather than one merged pool.
    expect($firstClient->careTeam()->pluck('users.id')->all())->toBe([$firstTherapist->id]);
    expect($secondClient->careTeam()->pluck('users.id')->all())->toBe([$secondTherapist->id]);

    // ...and its own billing account.
    expect(BillingAccount::where('client_id', $firstClient->id)->count())->toBe(1);
    expect(BillingAccount::where('client_id', $secondClient->id)->count())->toBe(1);

    // But the parent keeps a single login, and is only welcomed once —
    // the mailable is ShouldQueue, hence assertQueued rather than assertSent.
    expect(User::where('email', 'parent@example.com')->count())->toBe(1);
    expect($secondClient->user_id)->toBe($firstClient->user_id);
    Mail::assertQueued(WelcomeClientAccountMail::class, 1);
});

test('an already promoted intake cannot be approved twice', function () {
    $intake = Intake::factory()->create();
    Client::factory()->create(['original_intake_id' => $intake->id]);

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/approve", [])
        ->assertSessionHasErrors('therapist_id');

    expect(Client::count())->toBe(1);
});

test('a therapist approval promotes the intake and appends the client to the team member roster', function () {
    $intake = Intake::factory()->create();
    $therapist = therapistUser();
    $teamMember = TeamMember::factory()->create(['user_id' => $therapist->id, 'client' => []]);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $therapist->id,
        'status' => 'pending',
    ]);

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/therapist-approve")
        ->assertSessionHasNoErrors();

    expect(Client::count())->toBe(1);

    $client = Client::first();
    expect($intake->refresh()->approved_as_client)->toBeTrue();
    expect($intake->therapistReviews->first()->status)->toBe('approved');
    expect($teamMember->refresh()->client)->toBe([$client->id]);
    expect(IntakeTherapistApprovalHistory::where('status', 'approved')->count())->toBe(1);
});

test('a multi-service intake promotes to a client as soon as the first service is approved', function () {
    $intake = Intake::factory()->create([
        'services_needed' => ['Occupational Therapy', 'Speech and Language Therapy'],
    ]);
    $otTherapist = therapistUser();
    $slpTherapist = therapistUser();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $otTherapist->id,
        'service' => 'Occupational Therapy',
        'status' => 'pending',
    ]);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $slpTherapist->id,
        'service' => 'Speech and Language Therapy',
        'status' => 'pending',
    ]);

    $this->actingAs($otTherapist)
        ->post("/therapist/intake/{$intake->id}/therapist-approve", ['service' => 'Occupational Therapy'])
        ->assertSessionHasNoErrors();

    expect($intake->refresh()->approved_as_client)->toBeTrue();
    expect(Client::count())->toBe(1);

    $client = Client::first();
    expect($client->primary_therapist_id)->toBe($otTherapist->id);
    expect($client->careTeam()->pluck('users.id')->all())->toBe([$otTherapist->id]);

    // Approving the remaining service adds its therapist to the same client
    // instead of creating a second one.
    $this->actingAs($slpTherapist)
        ->post("/therapist/intake/{$intake->id}/therapist-approve", ['service' => 'Speech and Language Therapy'])
        ->assertSessionHasNoErrors();

    expect(Client::count())->toBe(1);
    expect($client->refresh()->primary_therapist_id)->toBe($otTherapist->id);
    expect($client->careTeam()->pluck('users.id')->sort()->values()->all())
        ->toBe(collect([$otTherapist->id, $slpTherapist->id])->sort()->values()->all());
});

test('approving a service records a ClientService so it shows up on the client, auto-provisioning the service offering if needed', function () {
    $intake = Intake::factory()->create([
        'services_needed' => ['Occupational Therapy', 'Counselling'],
    ]);
    $otTherapist = therapistUser();
    $counsellingTherapist = therapistUser();
    $existingOffering = ServiceOffering::factory()->create(['name' => 'Occupational Therapy']);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $otTherapist->id,
        'service' => 'Occupational Therapy',
        'status' => 'pending',
    ]);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $counsellingTherapist->id,
        'service' => 'Counselling',
        'status' => 'pending',
    ]);

    $this->actingAs($otTherapist)
        ->post("/therapist/intake/{$intake->id}/therapist-approve", ['service' => 'Occupational Therapy'])
        ->assertSessionHasNoErrors();
    $this->actingAs($counsellingTherapist)
        ->post("/therapist/intake/{$intake->id}/therapist-approve", ['service' => 'Counselling'])
        ->assertSessionHasNoErrors();

    $client = Client::first();
    expect(ClientService::where('client_id', $client->id)->count())->toBe(2);

    $otService = ClientService::where('client_id', $client->id)->where('service_id', $existingOffering->id)->first();
    expect($otService)->not->toBeNull();
    expect($otService->therapist_id)->toBe($otTherapist->id);

    $counsellingOffering = ServiceOffering::where('name', 'Counselling')->first();
    expect($counsellingOffering)->not->toBeNull();
    $counsellingService = ClientService::where('client_id', $client->id)->where('service_id', $counsellingOffering->id)->first();
    expect($counsellingService)->not->toBeNull();
    expect($counsellingService->therapist_id)->toBe($counsellingTherapist->id);

    $serviceNames = collect($client->refresh()->service_availed)->pluck('service_name')->sort()->values()->all();
    expect($serviceNames)->toBe(['Counselling', 'Occupational Therapy']);
});

test('rejecting one service review of a multi-service intake does not undo the client created by the other', function () {
    $intake = Intake::factory()->create([
        'services_needed' => ['Occupational Therapy', 'Speech and Language Therapy'],
    ]);
    $otTherapist = therapistUser();
    $slpTherapist = therapistUser();
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $otTherapist->id,
        'service' => 'Occupational Therapy',
        'status' => 'pending',
    ]);
    IntakeTherapistApproval::factory()->create([
        'intake_id' => $intake->id,
        'therapist_id' => $slpTherapist->id,
        'service' => 'Speech and Language Therapy',
        'status' => 'pending',
    ]);

    $this->actingAs($slpTherapist)
        ->post("/therapist/intake/{$intake->id}/therapist-approve", ['service' => 'Speech and Language Therapy'])
        ->assertSessionHasNoErrors();

    $this->actingAs($otTherapist)
        ->post("/therapist/intake/{$intake->id}/therapist-reject", ['service' => 'Occupational Therapy', 'notes' => 'At capacity'])
        ->assertSessionHasNoErrors();

    expect($intake->refresh()->approved_as_client)->toBeTrue();
    expect(Client::count())->toBe(1);
    expect(IntakeTherapistApproval::where('intake_id', $intake->id)->where('service', 'Speech and Language Therapy')->first()->status)->toBe('approved');
    expect(IntakeTherapistApproval::where('intake_id', $intake->id)->where('service', 'Occupational Therapy')->first()->status)->toBe('rejected');
});

test('a therapist approval requires a pending or reassigned review', function () {
    $intake = Intake::factory()->create();

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/therapist-approve")
        ->assertSessionHasErrors('service');

    expect(Client::count())->toBe(0);
});

test('a therapist rejection records notes and a history entry', function () {
    $intake = Intake::factory()->create();
    $review = IntakeTherapistApproval::factory()->create(['intake_id' => $intake->id, 'status' => 'pending']);

    $this->actingAs(adminUser())
        ->post("/admin/intake/{$intake->id}/therapist-reject", ['notes' => 'Caseload is full'])
        ->assertSessionHasNoErrors();

    $review->refresh();
    expect($review->status)->toBe('rejected');
    expect($review->notes)->toBe('Caseload is full');
    expect($review->decided_at)->not->toBeNull();

    $history = IntakeTherapistApprovalHistory::where('status', 'rejected')->first();
    expect($history->notes)->toBe('Caseload is full');
    expect(Client::count())->toBe(0);
});

test('the therapist reviews list can be filtered by status', function () {
    IntakeTherapistApproval::factory()->create(['status' => 'pending']);
    IntakeTherapistApproval::factory()->create(['status' => 'rejected']);

    $response = $this->actingAs(adminUser())->getJson('/admin/intake/therapist-reviews?status=pending');

    $response->assertOk();
    expect($response->json('total'))->toBe(1);
    expect($response->json('results.0.status'))->toBe('pending');
});

test('an admin can upload and delete an intake document', function () {
    Storage::fake('public');

    $intake = Intake::factory()->create(['timeline' => []]);

    $this->actingAs(adminUser())->post("/admin/intake/{$intake->id}/documents", [
        'type' => 'Medical Report',
        'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    expect(IntakeDocument::count())->toBe(1);

    $document = IntakeDocument::first();
    expect($document->intake_id)->toBe($intake->id);
    expect($document->type)->toBe('Medical Report');
    Storage::disk('public')->assertExists($document->drive_file_id);
    expect($intake->refresh()->timeline[0]['title'])->toBe('Uploaded a new File Medical Report');

    $this->actingAs(adminUser())->delete("/admin/intake/documents/{$document->id}")->assertSessionHasNoErrors();

    expect(IntakeDocument::count())->toBe(0);
    Storage::disk('public')->assertMissing($document->drive_file_id);
});

test('an admin can bulk upload multiple intake documents', function () {
    Storage::fake('public');

    $intake = Intake::factory()->create(['timeline' => []]);

    $this->actingAs(adminUser())->post("/admin/intake/{$intake->id}/documents/multiple", [
        'documents' => [
            ['type' => 'Medical Report', 'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf')],
            ['type' => 'Referral Letter', 'name' => 'Referral', 'file' => UploadedFile::fake()->create('referral.pdf', 100, 'application/pdf')],
        ],
    ])->assertSessionHasNoErrors();

    expect(IntakeDocument::count())->toBe(2);
    expect(IntakeDocument::where('type', 'Referral Letter')->first()->name)->toBe('Referral');
    expect($intake->refresh()->timeline[0]['title'])->toBe('Uploaded 2 new documents');
});

test('an admin can update an existing intake document', function () {
    Storage::fake('public');

    $intake = Intake::factory()->create(['timeline' => []]);
    $document = $intake->documents()->create([
        'name' => 'Original',
        'type' => 'Medical Report',
        'drive_file_id' => 'intake-documents/original.pdf',
        'uploaded_at' => now(),
    ]);

    $this->actingAs(adminUser())->patch("/admin/intake/{$intake->id}/documents", [
        'document_id' => $document->id,
        'name' => 'Updated Name',
        'file' => UploadedFile::fake()->create('updated.pdf', 100, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $document->refresh();
    expect($document->name)->toBe('Updated Name');
    expect($document->type)->toBe('Medical Report');
    Storage::disk('public')->assertExists($document->drive_file_id);
    expect($intake->refresh()->timeline[0]['title'])->toBe('Updated document Medical Report');
});

test('an admin can add and delete internal notes', function () {
    $intake = Intake::factory()->create(['notes' => [], 'timeline' => []]);
    $admin = adminUser();

    $this->actingAs($admin)->patch("/admin/intake/{$intake->id}/notes", ['note' => 'Called the parent'])
        ->assertSessionHasNoErrors();

    $intake->refresh();
    expect($intake->notes)->toHaveCount(1);
    expect($intake->notes[0]['note'])->toBe('Called the parent');
    expect($intake->notes[0]['user'])->toBe($admin->full_name);
    expect($intake->timeline[0]['title'])->toBe('Internal note added');

    $noteId = $intake->notes[0]['id'];

    $this->actingAs($admin)->delete("/admin/intake/{$intake->id}/notes/{$noteId}")->assertSessionHasNoErrors();

    expect($intake->refresh()->notes)->toHaveCount(0);
});

test('the detail page exposes the intake, therapists and allowed status transitions', function () {
    $intake = Intake::factory()->create(['status' => 'under_review']);
    $therapist = therapistUser();
    TeamMember::factory()->create(['user_id' => $therapist->id, 'specializations' => ['Counselling']]);

    $this->actingAs(adminUser())->get("/admin/intake/{$intake->id}")
        ->assertInertia(fn ($page) => $page
            ->component('admin/intake/show')
            ->where('intake.id', $intake->id)
            ->has('therapists', 1)
            ->where('therapists.0.specializations', ['Counselling'])
            ->where('statusTransitions', ['approved', 'waitlist', 'denied'])
        );
});
