<?php

use App\Models\Application;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the application index shows stats, search, and status filter', function () {
    Application::factory()->create(['application_status' => 'pending']);
    Application::factory()->create(['application_status' => 'hired', 'first_name' => 'Zoe']);

    $response = $this->actingAs(adminUser())->get('/admin/applications');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/applications/index')
        ->where('stats.total', 2)
        ->where('stats.pending', 1)
        ->where('stats.hired', 1)
    );

    $filtered = $this->actingAs(adminUser())->get('/admin/applications?search=Zoe');
    $filtered->assertInertia(fn ($page) => $page->has('applications.data', 1));
});

test('an invalid status transition is rejected', function () {
    $application = Application::factory()->create(['application_status' => 'pending']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
        'hourly_rate' => 40,
    ])->assertSessionHasErrors('application_status');

    expect($application->refresh()->application_status)->toBe('pending');
});

test('scheduling an interview requires and stores the interview fields', function () {
    $application = Application::factory()->create(['application_status' => 'reviewing']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'interview_scheduled',
    ])->assertSessionHasErrors('interview_date');

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'interview_scheduled',
        'interview_date' => now()->addDays(3)->toDateString(),
        'interview_time' => '14:00',
        'interview_platform' => 'video',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('interview_scheduled');
    expect($application->interview_platform)->toBe('video');
});

test('hiring an applicant creates a therapist user and a linked team member', function () {
    $application = Application::factory()->create([
        'application_status' => 'reviewing',
        'email' => 'new.hire@example.com',
        'first_name' => 'Jamie',
        'last_name' => 'Rivera',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
        'hourly_rate' => 55,
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('hired');
    expect($application->hired)->toBeTrue();
    expect((float) $application->hourly_rate)->toBe(55.0);
    expect($application->hire_date)->not->toBeNull();

    $user = User::where('email', 'new.hire@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('therapist');

    $teamMember = TeamMember::where('user_id', $user->id)->first();
    expect($teamMember)->not->toBeNull();
    expect($teamMember->application_id)->toBe($application->id);
    expect((float) $teamMember->hourly_rate)->toBe(55.0);
});

test('hiring an applicant whose email already belongs to a user reuses that user', function () {
    $existing = User::factory()->create(['email' => 'already.here@example.com', 'role' => 'client']);
    $application = Application::factory()->create([
        'application_status' => 'reviewing',
        'email' => 'already.here@example.com',
    ]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
        'hourly_rate' => 50,
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'already.here@example.com')->count())->toBe(1);
    expect(TeamMember::where('user_id', $existing->id)->exists())->toBeTrue();
});

test('an application cannot be hired twice', function () {
    $application = Application::factory()->create([
        'application_status' => 'hired',
        'hired' => true,
    ]);

    // hired is terminal, so the status-transition guard already blocks
    // this — but exercise the service guard directly too via a forced
    // reviewing state to prove double-hire protection independent of the
    // transition table.
    $application->forceFill(['application_status' => 'reviewing'])->save();

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'hired',
        'hourly_rate' => 60,
    ])->assertSessionHasErrors();
});

test('declining an application sets the declined flag', function () {
    $application = Application::factory()->create(['application_status' => 'reviewing']);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/status", [
        'application_status' => 'declined',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->application_status)->toBe('declined');
    expect($application->declined)->toBeTrue();
});

test('candidate rating must be between 1 and 5', function () {
    $application = Application::factory()->create();

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/rating", [
        'candidate_rating' => 6,
    ])->assertSessionHasErrors('candidate_rating');

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/rating", [
        'candidate_rating' => 4,
    ])->assertSessionHasNoErrors();

    expect($application->refresh()->candidate_rating)->toBe(4);
});

test('an admin can add and delete internal notes', function () {
    $application = Application::factory()->create(['internal_notes' => []]);

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/notes", [
        'note' => 'Strong candidate.',
    ])->assertSessionHasNoErrors();

    $application->refresh();
    expect($application->internal_notes)->toHaveCount(1);
    $noteId = $application->internal_notes[0]['id'];

    $this->actingAs(adminUser())->delete("/admin/applications/{$application->id}/notes/{$noteId}")
        ->assertSessionHasNoErrors();

    expect($application->refresh()->internal_notes)->toBe([]);
});

test('an admin can delete an application', function () {
    $application = Application::factory()->create();

    $this->actingAs(adminUser())->delete("/admin/applications/{$application->id}")->assertSessionHasNoErrors();

    expect(Application::find($application->id))->toBeNull();
});
