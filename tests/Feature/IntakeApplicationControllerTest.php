<?php

use App\Models\ConsentDocument;
use App\Models\Intake;
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
        'available_days' => ['Monday', 'Tuesday'],
        'preferred_times' => ['Mornings (8am-11am)'],
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

test('mismatched primary parent emails fail validation', function () {
    $payload = validIntakePayload(['primary_parent_email_confirm' => 'different@example.com']);

    $response = $this->post('/intake/apply', $payload);

    $response->assertSessionHasErrors(['primary_parent_email_confirm']);

    expect(Intake::count())->toBe(0);
});
