<?php

use App\Models\Career;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('the team index shows stats with live caseload and filters', function () {
    $therapistA = therapistUser();
    $therapistB = therapistUser();
    TeamMember::factory()->create(['user_id' => $therapistA->id, 'employment_status' => 'active']);
    TeamMember::factory()->create(['user_id' => $therapistB->id, 'employment_status' => 'inactive']);

    Client::factory()->create(['primary_therapist_id' => $therapistA->id]);
    Client::factory()->create(['primary_therapist_id' => $therapistA->id]);

    $response = $this->actingAs(adminUser())->get('/admin/team');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/team/index')
        ->where('stats.total', 2)
        ->where('stats.active', 1)
        ->where('stats.caseload', 2)
    );
});

test('the edit page exposes the team member with their user first and last name', function () {
    $therapist = User::factory()->therapist()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $teamMember = TeamMember::factory()->create(['user_id' => $therapist->id]);

    $this->actingAs(adminUser())->get("/admin/team/edit/{$teamMember->id}")
        ->assertInertia(fn ($page) => $page
            ->component('admin/team/edit')
            ->where('teamMember.user.first_name', 'Jane')
            ->where('teamMember.user.last_name', 'Doe')
        );
});

test('an admin can create a team member which creates a paired user', function () {
    $payload = validTeamMemberPayload(['email' => 'new.therapist@example.com']);

    $this->actingAs(adminUser())->post('/admin/team', $payload)->assertSessionHasNoErrors();

    $user = User::where('email', 'new.therapist@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('therapist');

    $teamMember = TeamMember::where('user_id', $user->id)->first();
    expect($teamMember)->not->toBeNull();
    expect($teamMember->position)->toBe('Occupational Therapist');
});

test('SIN is hashed on write and never exposed as the submitted plaintext', function () {
    $payload = validTeamMemberPayload(['email' => 'sin.test@example.com', 'sin_number' => '123456789']);

    $response = $this->actingAs(adminUser())->post('/admin/team', $payload);
    $response->assertSessionHasNoErrors();

    $teamMember = TeamMember::whereHas('user', fn ($q) => $q->where('email', 'sin.test@example.com'))->first();
    expect($teamMember->sin_number)->not->toBe('123456789');
    expect($teamMember->sin_number)->toBe(hash('sha256', '123456789'));

    $showResponse = $this->actingAs(adminUser())->get("/admin/team/{$teamMember->id}");
    $showResponse->assertOk();
    expect($showResponse->getContent())->not->toContain('123456789');
});

test('updating access syncs the linked user is_active state per employment status', function () {
    $therapist = therapistUser();
    $teamMember = TeamMember::factory()->create(['user_id' => $therapist->id, 'employment_status' => 'active']);

    $this->actingAs(adminUser())->patch("/admin/team/{$teamMember->id}/access", [
        'can_access_finance' => false,
        'can_manage_team' => false,
        'can_manage_clients' => true,
        'employment_status' => 'terminated',
    ])->assertSessionHasNoErrors();

    expect($therapist->refresh()->is_active)->toBeFalse();

    $this->actingAs(adminUser())->patch("/admin/team/{$teamMember->id}/access", [
        'can_access_finance' => false,
        'can_manage_team' => false,
        'can_manage_clients' => true,
        'employment_status' => 'active',
    ])->assertSessionHasNoErrors();

    expect($therapist->refresh()->is_active)->toBeTrue();
});

test('the missing-required-docs diff compares uploaded documents against the career position', function () {
    $therapist = therapistUser();
    $teamMember = TeamMember::factory()->create([
        'user_id' => $therapist->id,
        'position' => 'Occupational Therapist',
    ]);
    Career::factory()->create([
        'position' => 'Occupational Therapist',
        'required_documents' => ['Police Check', 'First Aid Certificate'],
    ]);

    ClientDocument::query()->create([
        'user_id' => $therapist->id,
        'title' => 'First Aid',
        'doc_type' => 'First Aid Certificate',
        'drive_file_url' => 'team-member-documents/existing.pdf',
        'upload_origin' => 'admin',
        'uploaded_at' => now(),
    ]);

    $response = $this->actingAs(adminUser())->get("/admin/team/{$teamMember->id}");

    $response->assertInertia(fn ($page) => $page
        ->component('admin/team/show')
        ->where('missingDocuments', ['Police Check'])
    );
});

test('an admin can upload and delete a team member document', function () {
    Storage::fake('public');

    $therapist = therapistUser();
    $teamMember = TeamMember::factory()->create(['user_id' => $therapist->id]);

    $this->actingAs(adminUser())->post("/admin/team/{$teamMember->id}/documents", [
        'type' => 'Police Check',
        'file' => UploadedFile::fake()->create('check.pdf', 100, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $document = ClientDocument::where('user_id', $therapist->id)->first();
    expect($document)->not->toBeNull();
    expect($teamMember->refresh()->documents)->toBe([$document->id]);
    Storage::disk('public')->assertExists($document->drive_file_id);

    $this->actingAs(adminUser())->delete("/admin/team/documents/{$document->id}")->assertSessionHasNoErrors();

    expect(ClientDocument::find($document->id))->toBeNull();
    expect($teamMember->refresh()->documents)->toBe([]);
});

test('deleting a team member does not delete the linked user', function () {
    $therapist = therapistUser();
    $teamMember = TeamMember::factory()->create(['user_id' => $therapist->id]);

    $this->actingAs(adminUser())->delete("/admin/team/{$teamMember->id}")->assertSessionHasNoErrors();

    expect(TeamMember::find($teamMember->id))->toBeNull();
    expect(User::find($therapist->id))->not->toBeNull();
});

test('a therapist can view and update their own restricted profile fields', function () {
    $therapist = therapistUser();
    $teamMember = TeamMember::factory()->create([
        'user_id' => $therapist->id,
        'hourly_rate' => 75,
        'maximum_caseload' => 10,
    ]);

    $this->actingAs($therapist)->get('/therapist/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('therapist/profile')
            ->has('documents')
            ->has('missingDocuments')
        );

    $this->actingAs($therapist)->put('/therapist/profile', [
        'phone' => '555-0100',
        'emergency_contact_name' => 'Jamie Doe',
        'emergency_contact_phone' => '555-0199',
    ])->assertSessionHasNoErrors();

    $teamMember->refresh();
    expect($teamMember->phone)->toBe('555-0100');
    expect((float) $teamMember->hourly_rate)->toBe(75.0);
    expect($teamMember->maximum_caseload)->toBe(10);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validTeamMemberPayload(array $overrides = []): array
{
    return array_merge([
        'email' => 'therapist@example.com',
        'first_name' => 'Alex',
        'last_name' => 'Rivera',
        'phone' => '555-0100',
        'street_address' => '123 Main St',
        'city' => 'Calgary',
        'province' => 'Alberta',
        'zip_code' => 'T2P 1J9',
        'resident_status' => 'Citizen',
        'position' => 'Occupational Therapist',
        'employment_status' => 'active',
        'hire_date' => now()->toDateString(),
        'hourly_rate' => 60,
        'maximum_caseload' => 15,
        'availability' => collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])
            ->map(fn (string $day) => ['week_day' => $day, 'time_from' => null, 'time_to' => null])
            ->all(),
        'emergency_contact_name' => 'Sam Rivera',
        'emergency_contact_phone' => '555-0111',
        'can_access_finance' => false,
        'can_manage_team' => false,
        'can_manage_clients' => true,
    ], $overrides);
}

test('an admin can export a team member profile as a PDF', function () {
    $therapist = User::factory()->therapist()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane.doe@example.com',
    ]);
    $teamMember = TeamMember::factory()->create([
        'user_id' => $therapist->id,
        'position' => 'Speech Language Pathologist',
        'credentials' => ['SLP', 'RSLP'],
        'specializations' => ['Speech and Language Therapy'],
        'emergency_contact_name' => 'John Doe',
        'availability' => [
            ['week_day' => 'Monday', 'time_from' => '09:00', 'time_to' => '17:00'],
            ['week_day' => 'Tuesday', 'time_from' => '', 'time_to' => ''],
        ],
        'sin_number' => '123456789',
    ]);

    $response = $this->actingAs(adminUser())->get("/admin/team/{$teamMember->id}/pdf");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $response->assertDownload('jane-doe-profile.pdf');

    $pdf = $response->streamedContent();
    expect($pdf)->toStartWith('%PDF');

    // The hash must never reach the document, nor the plaintext it came from.
    expect($pdf)->not->toContain(hash('sha256', '123456789'))
        ->and($pdf)->not->toContain('123456789');
});

test('a therapist cannot export a team member profile', function () {
    $teamMember = TeamMember::factory()->create(['user_id' => therapistUser()->id]);

    // The role middleware bounces non-admins to their own home rather than
    // answering 403 — same as every other admin route.
    $this->actingAs(therapistUser())->get("/admin/team/{$teamMember->id}/pdf")
        ->assertRedirect('/therapist');
});
