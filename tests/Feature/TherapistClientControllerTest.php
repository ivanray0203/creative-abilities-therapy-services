<?php

use App\Models\Client;
use App\Models\Intake;

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
