<?php

use App\Models\Client;
use App\Models\Intake;
use App\Models\ScheduleSession;

test('the client list scopes to primary, assigned, and care-team relationships', function () {
    $therapist = therapistUser();
    $otherTherapist = therapistUser();

    $primary = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    $assigned = Client::factory()->create(['assigned_therapist_id' => $therapist->id]);
    $careTeam = Client::factory()->create();
    $careTeam->careTeam()->attach($therapist->id);
    Client::factory()->create(['primary_therapist_id' => $otherTherapist->id]);

    $response = $this->actingAs($therapist)->get('/therapist/clients');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('therapist/clients/index')
        ->has('clients.data', 3)
    );

    $ids = collect([$primary, $assigned, $careTeam])->pluck('id')->all();
    expect($ids)->each->toBeInt();
});

test('the active/inactive status filter and search scope the client list', function () {
    $therapist = therapistUser();
    $activeIntake = Intake::factory()->create(['child_first_name' => 'Zoe', 'child_last_name' => 'Adler']);
    Client::factory()->create([
        'primary_therapist_id' => $therapist->id,
        'status' => 'active',
        'original_intake_id' => $activeIntake->id,
    ]);
    Client::factory()->create(['primary_therapist_id' => $therapist->id, 'status' => 'inactive']);

    $activeResponse = $this->actingAs($therapist)->get('/therapist/clients?status=active');
    $activeResponse->assertInertia(fn ($page) => $page->has('clients.data', 1));

    $searchResponse = $this->actingAs($therapist)->get('/therapist/clients?status=active&search=Zoe');
    $searchResponse->assertInertia(fn ($page) => $page->has('clients.data', 1));

    $noMatchResponse = $this->actingAs($therapist)->get('/therapist/clients?status=active&search=Nobody');
    $noMatchResponse->assertInertia(fn ($page) => $page->has('clients.data', 0));
});

test('the client list paginates and reports a total matching the rows returned', function () {
    $therapist = therapistUser();

    Client::factory()->count(17)->create([
        'primary_therapist_id' => $therapist->id,
        'status' => 'active',
    ]);

    // A client reachable through the care team as well as the primary
    // assignment — the scope must not count them twice.
    $shared = Client::factory()->create([
        'primary_therapist_id' => $therapist->id,
        'status' => 'active',
    ]);
    $shared->careTeam()->attach($therapist->id);

    $this->actingAs($therapist)->get('/therapist/clients')
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 15)
            ->where('clients.total', 18)
            ->where('clients.per_page', 15)
            ->where('clients.current_page', 1)
            ->where('clients.last_page', 2)
        );

    $this->actingAs($therapist)->get('/therapist/clients?page=2')
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 3)
            ->where('clients.current_page', 2)
        );
});

test('the caseload flags which clients have contracted hours left to schedule', function () {
    $therapist = therapistUser();

    $bookable = Client::factory()->create(['primary_therapist_id' => $therapist->id, 'status' => 'active']);
    contractedService($bookable, $therapist);

    // A one-hour contract, spent by a one-hour session.
    $fullyBooked = Client::factory()->create(['primary_therapist_id' => $therapist->id, 'status' => 'active']);
    $booked = contractedService($fullyBooked, $therapist, contract: ['allotted_hours' => 1]);
    ScheduleSession::factory()->linkedTo($booked)->create([
        'client_id' => $fullyBooked->id,
        'therapist_id' => $therapist->id,
        'status' => 'scheduled',
    ]);

    // Someone else's service on this therapist's client is not theirs to book.
    $otherTherapists = Client::factory()->create(['primary_therapist_id' => $therapist->id, 'status' => 'active']);
    contractedService($otherTherapists, therapistUser());

    $this->actingAs($therapist)->get('/therapist/clients')
        ->assertInertia(function ($page) use ($bookable, $fullyBooked, $otherTherapists) {
            $flags = collect($page->toArray()['props']['clients']['data'])
                ->pluck('has_bookable_service', 'id');

            expect($flags[$bookable->id])->toBeTrue()
                ->and($flags[$fullyBooked->id])->toBeFalse()
                ->and($flags[$otherTherapists->id])->toBeFalse();

            return true;
        });
});

test('the session form preselects a client passed from the caseload, ignoring one it cannot offer', function () {
    $therapist = therapistUser();

    $bookable = Client::factory()->create(['primary_therapist_id' => $therapist->id]);
    contractedService($bookable, $therapist);

    $this->actingAs($therapist)->get("/therapist/sessions/create?client_id={$bookable->id}")
        ->assertInertia(fn ($page) => $page->where('preselectedClientId', $bookable->id));

    $stranger = Client::factory()->create();

    $this->actingAs($therapist)->get("/therapist/sessions/create?client_id={$stranger->id}")
        ->assertInertia(fn ($page) => $page->where('preselectedClientId', null));
});
