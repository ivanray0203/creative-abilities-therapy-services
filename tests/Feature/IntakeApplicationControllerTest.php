<?php

use App\Models\ConsentDocument;
use App\Models\Intake;
use App\Models\User;
use App\Models\UserConsentAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validIntakePayload(array $overrides = []): array
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
        'availability_slots' => [
            'Monday' => ['Mornings (8am-11am)'],
            'Tuesday' => ['Mornings (8am-11am)'],
        ],
        'primary_parent_name' => 'Jane Doe',
        'primary_parent_phone' => '5874338780',
        'primary_parent_email' => 'jane@example.com',
        'primary_parent_email_confirm' => 'jane@example.com',
        'primary_relationship_to_child' => 'mother',
        'primary_contact_method' => 'email',
        'emergency_contact_name' => 'Jane Doe',
        'emergency_contact_phone' => '5874338780',
        'emergency_contact_relationship' => 'mother',
        'referral_source' => 'Online Search (Google, etc.)',
        'terms_accepted' => true,
        'privacy_accepted' => true,
        'consent_ids' => [],
    ], $overrides);
}

test('a visitor can view the intake application page', function () {
    $response = $this->get('/intake/apply');

    $response->assertSuccessful();
});

test('a visitor can submit a private-pay intake application', function () {
    $document = ConsentDocument::factory()->create(['purpose' => 'intake', 'is_active' => true]);

    $payload = validIntakePayload(['consent_ids' => [$document->id]]);

    $response = $this->post('/intake/apply', $payload);

    $response->assertRedirect();
    $response->assertSessionHas('success', true);
    $response->assertSessionHas('reference_number');

    expect(Intake::count())->toBe(1);

    $intake = Intake::first();
    expect($intake->status)->toBe('pending');
    expect($intake->reference_number)->toMatch('/^INT-'.now()->year.'-\d{3}$/');
    expect($intake->funding_source_info)->toBe(['consents' => []]);
    expect($intake->timeline[0]['title'])->toBe('Intake form submitted via website');

    expect(UserConsentAcceptance::count())->toBe(1);
    $acceptance = UserConsentAcceptance::first();
    expect($acceptance->document_id)->toBe($document->id);
    expect($acceptance->user_id)->toBeNull();
    expect($acceptance->is_revoked)->toBeFalse();
});

test('an fscd funding source requires fscd case worker fields and produces hardcoded fscd consents', function () {
    $payload = validIntakePayload([
        'funding_source' => 'BDS-FSCD',
        'fscd_info' => [
            'FSCD_case_worker_name' => 'Worker Name',
            'FSCD_case_worker_email' => 'worker@example.com',
            'FSCD_approval_start_date' => now()->toDateString(),
        ],
    ]);

    $response = $this->post('/intake/apply', $payload);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $intake = Intake::first();
    expect($intake->funding_source_info['FSCD_case_worker_name'])->toBe('Worker Name');
    expect($intake->funding_source_info['consents'])->toHaveCount(3);
});

test('the fscd case worker name is required when funding source is an fscd variant', function () {
    $payload = validIntakePayload(['funding_source' => 'BDS-FSCD']);

    $response = $this->post('/intake/apply', $payload);

    $response->assertSessionHasErrors([
        'fscd_info.FSCD_case_worker_name',
        'fscd_info.FSCD_case_worker_email',
        'fscd_info.FSCD_approval_start_date',
    ]);

    expect(Intake::count())->toBe(0);
});

test('an ss-fscd application can request the clinical coordinator service', function () {
    $payload = validIntakePayload([
        'funding_source' => 'SS-FSCD',
        'fscd_info' => [
            'FSCD_case_worker_name' => 'Worker Name',
            'FSCD_case_worker_email' => 'worker@example.com',
            'FSCD_approval_start_date' => now()->toDateString(),
        ],
        'services_needed' => ['Clinical Coordinator', 'Occupational Therapy'],
    ]);

    $this->post('/intake/apply', $payload)->assertSessionHasNoErrors();

    expect(Intake::first()->services_needed)->toBe([
        'Clinical Coordinator',
        'Occupational Therapy',
    ]);
});

test('the availability grid is stored and the flat day/time lists are derived from it', function () {
    $payload = validIntakePayload([
        'availability_slots' => [
            'Wednesday' => ['Evenings (4pm-7pm)', 'Mornings (8am-11am)'],
            'Monday' => ['Mornings (8am-11am)'],
        ],
    ]);

    $this->post('/intake/apply', $payload)->assertSessionHasNoErrors();

    $intake = Intake::first();

    /*
     * Times within a day are normalised into taxonomy order, not the order
     * they arrived in. The day keys are compared without assuming an order —
     * MySQL does not preserve JSON object key order on read.
     */
    expect($intake->availability_slots)->toHaveCount(2);
    expect($intake->availability_slots['Monday'])->toBe(['Mornings (8am-11am)']);
    expect($intake->availability_slots['Wednesday'])->toBe([
        'Mornings (8am-11am)',
        'Evenings (4pm-7pm)',
    ]);
    expect($intake->available_days)->toBe(['Monday', 'Wednesday']);
    expect($intake->preferred_times)->toBe([
        'Mornings (8am-11am)',
        'Evenings (4pm-7pm)',
    ]);
});

test('an intake cannot be submitted without any availability', function () {
    $this->post('/intake/apply', validIntakePayload(['availability_slots' => []]))
        ->assertSessionHasErrors(['availability_slots']);

    expect(Intake::count())->toBe(0);
});

test('an unknown availability day or time is rejected', function () {
    $this->post('/intake/apply', validIntakePayload([
        'availability_slots' => ['Funday' => ['Mornings (8am-11am)']],
    ]))->assertSessionHasErrors(['availability_slots']);

    $this->post('/intake/apply', validIntakePayload([
        'availability_slots' => ['Monday' => ['Midnight']],
    ]))->assertSessionHasErrors(['availability_slots.Monday.0']);

    expect(Intake::count())->toBe(0);
});

test('an other diagnosis is stored as the free-text answer instead of the "Other" label', function () {
    $payload = validIntakePayload([
        'diagnosis' => ['Autism Spectrum Disorder (ASD)', 'Other'],
        'diagnosis_other' => 'Cerebral Palsy',
    ]);

    $this->post('/intake/apply', $payload)->assertSessionHasNoErrors();

    expect(Intake::first()->diagnosis)->toBe([
        'Autism Spectrum Disorder (ASD)',
        'Cerebral Palsy',
    ]);
});

test('the other diagnosis free text is required when "Other" is selected', function () {
    $payload = validIntakePayload(['diagnosis' => ['Other']]);

    $this->post('/intake/apply', $payload)->assertSessionHasErrors(['diagnosis_other']);

    expect(Intake::count())->toBe(0);
});

test('the other diagnosis free text is ignored when "Other" is not selected', function () {
    $payload = validIntakePayload([
        'diagnosis' => ['Down Syndrome'],
        'diagnosis_other' => 'Cerebral Palsy',
    ]);

    $this->post('/intake/apply', $payload)->assertSessionHasNoErrors();

    expect(Intake::first()->diagnosis)->toBe(['Down Syndrome']);
});

test('mismatched primary parent emails fail validation', function () {
    $payload = validIntakePayload(['primary_parent_email_confirm' => 'different@example.com']);

    $response = $this->post('/intake/apply', $payload);

    $response->assertSessionHasErrors(['primary_parent_email_confirm']);

    expect(Intake::count())->toBe(0);
});

/**
 * Phase 17 — a parent who already has an account must register additional
 * children from inside the portal, so every child stays under one login.
 *
 * @see tasks/17-multi-child-client-model.md
 */
test('a parent who already has a client account cannot submit the public intake form', function () {
    User::factory()->client()->create(['email' => 'jane@example.com']);

    $this->post('/intake/apply', validIntakePayload())
        ->assertSessionHasErrors('primary_parent_email');

    expect(Intake::count())->toBe(0);
});

test('a pending intake alone does not block a later public submission', function () {
    // No account exists yet — the first intake is still under review, so the
    // parent has nowhere to log in and must be able to submit again.
    Intake::factory()->create(['primary_parent_email' => 'jane@example.com']);

    $this->post('/intake/apply', validIntakePayload())
        ->assertSessionHasNoErrors();

    expect(Intake::where('primary_parent_email', 'jane@example.com')->count())->toBe(2);
});

test('a therapist account does not block a public intake submission', function () {
    User::factory()->therapist()->create(['email' => 'jane@example.com']);

    $this->post('/intake/apply', validIntakePayload())
        ->assertSessionHasNoErrors();

    expect(Intake::count())->toBe(1);
});

test('a signed-in parent can register another child from the portal', function () {
    $client = clientWithUser();
    $parent = $client->user;

    $payload = validIntakePayload([
        'child_first_name' => 'Maria',
        'primary_parent_email' => $parent->email,
        'primary_parent_email_confirm' => $parent->email,
    ]);

    $this->actingAs($parent)->post('/client/intake', $payload)
        ->assertSessionHasNoErrors();

    $intake = Intake::where('child_first_name', 'Maria')->first();
    expect($intake)->not->toBeNull();
    expect($intake->submitted_by_id)->toBe($parent->id);
    expect($intake->status)->toBe('pending');
});

test('a portal intake must use the email on the parent\'s own account', function () {
    $parent = clientWithUser()->user;

    $payload = validIntakePayload([
        'primary_parent_email' => 'someone-else@example.com',
        'primary_parent_email_confirm' => 'someone-else@example.com',
    ]);

    $this->actingAs($parent)->post('/client/intake', $payload)
        ->assertSessionHasErrors('primary_parent_email');

    expect(Intake::where('primary_parent_email', 'someone-else@example.com')->count())->toBe(0);
});

test('the portal intake form prefills the parent details already on file', function () {
    $client = clientWithUser();

    $this->actingAs($client->user)->get('/client/intake/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('client/intake/create')
            ->where('prefill.primary_parent_email', $client->user->email)
            ->where('prefill.city', $client->originalIntake->city)
        );
});

test('the portal intake form locks the parent and emergency fields it prefilled', function () {
    $client = clientWithUser();
    $client->originalIntake->update([
        'primary_parent_name' => 'Maria Reyes',
        'primary_parent_phone' => '5874338780',
        'primary_relationship_to_child' => 'mother',
        'primary_contact_method' => 'email',
        'emergency_contact_name' => 'Jose Reyes',
        'emergency_contact_phone' => '5874338781',
        'emergency_contact_relationship' => 'father',
    ]);

    $this->actingAs($client->user)->get('/client/intake/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('prefill.primary_parent_name', 'Maria Reyes')
            ->where('prefill.primary_parent_phone', '5874338780')
            ->where('prefill.primary_relationship_to_child', 'mother')
            ->where('prefill.primary_contact_method', 'email')
            ->where('prefill.emergency_contact_name', 'Jose Reyes')
            ->where('prefill.emergency_contact_phone', '5874338781')
            ->where('prefill.emergency_contact_relationship', 'father')
        );
});

test('a parent field with nothing on file is left blank so it stays editable', function () {
    // The page only locks fields that arrive pre-filled — a null here keeps
    // the required field editable rather than leaving the form unsubmittable.
    $client = clientWithUser();
    $client->user->update(['phone' => null]);
    $client->originalIntake->update([
        'primary_relationship_to_child' => null,
        'primary_parent_phone' => null,
        'emergency_contact_name' => null,
    ]);

    $this->actingAs($client->user)->get('/client/intake/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('prefill.primary_relationship_to_child', null)
            ->where('prefill.primary_parent_phone', null)
            ->where('prefill.emergency_contact_name', null)
            ->where('prefill.primary_parent_email', $client->user->email)
        );
});

test('the intakes list shows every intake the parent has submitted', function () {
    $client = clientWithUser();
    $parent = $client->user;

    // Their first child's intake came in through the public form, before the
    // account existed — so it has no submitted_by_id.
    $firstBorn = $client->originalIntake;
    $firstBorn->update(['primary_parent_email' => $parent->email, 'submitted_by_id' => null]);

    $secondBorn = Intake::factory()->create([
        'primary_parent_email' => $parent->email,
        'submitted_by_id' => $parent->id,
    ]);

    Intake::factory()->create(['primary_parent_email' => 'someone-else@example.com']);

    $this->actingAs($parent)->get('/client/intake')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('client/intake/index')
            ->has('intakes.data', 2)
            ->where('intakes.data.0.id', $secondBorn->id)
            ->where('intakes.data.1.id', $firstBorn->id)
        );
});

test('the intakes list can be searched by child name and reference number', function () {
    $client = clientWithUser();
    $parent = $client->user;
    $client->originalIntake->update(['primary_parent_email' => $parent->email]);

    $match = Intake::factory()->create([
        'primary_parent_email' => $parent->email,
        'child_first_name' => 'Zephyr',
        'reference_number' => 'INT-2026-999',
    ]);

    $this->actingAs($parent)->get('/client/intake?search=Zephyr')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 1)
            ->where('intakes.data.0.id', $match->id)
            ->where('filters.search', 'Zephyr')
        );

    $this->actingAs($parent)->get('/client/intake?search=INT-2026-999')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 1)
            ->where('intakes.data.0.id', $match->id)
        );
});

test('a parent cannot see another family\'s intakes in the list', function () {
    $parent = clientWithUser()->user;
    $stranger = Intake::factory()->create(['primary_parent_email' => 'stranger@example.com']);

    $this->actingAs($parent)->get('/client/intake')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'intakes.data',
            fn ($rows) => collect($rows)->doesntContain('id', $stranger->id),
        ));
});

/**
 * The confirmation modal reads `flash.reference_number` off the Inertia page
 * props. It was flashed to the session but never shared, so the reference
 * number silently never rendered.
 */
test('the reference number reaches the page props after a portal submission', function () {
    $client = clientWithUser();
    $parent = $client->user;

    $payload = validIntakePayload([
        'child_first_name' => 'Maria',
        'primary_parent_email' => $parent->email,
        'primary_parent_email_confirm' => $parent->email,
    ]);

    $this->actingAs($parent)->post('/client/intake', $payload);

    $reference = Intake::where('child_first_name', 'Maria')->value('reference_number');
    expect($reference)->not->toBeNull();

    $this->actingAs($parent)->get('/client/intake/create')
        ->assertInertia(fn ($page) => $page->where('flash.reference_number', $reference));
});

test('the reference number reaches the page props after a public submission', function () {
    $this->post('/intake/apply', validIntakePayload());

    $reference = Intake::first()->reference_number;

    $this->get('/intake/apply')
        ->assertInertia(fn ($page) => $page->where('flash.reference_number', $reference));
});

test('validation messages name their field in plain language', function () {
    $payload = validIntakePayload(['funding_source' => 'BDS-FSCD']);

    $this->post('/intake/apply', $payload)->assertSessionHasErrors([
        'fscd_info.FSCD_approval_start_date' => 'The FSCD approval start date field is required.',
    ]);

    $this->post('/intake/apply', validIntakePayload(['child_first_name' => '']))
        ->assertSessionHasErrors([
            'child_first_name' => "The child's first name field is required.",
        ]);
});
