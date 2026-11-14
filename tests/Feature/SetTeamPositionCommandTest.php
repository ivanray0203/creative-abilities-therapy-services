<?php

use App\Models\Career;
use App\Models\TeamMember;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Career::factory()->create(['position' => 'Behavioural & Developmental Aide']);
    Career::factory()->create(['position' => 'Occupational Therapist (OT)']);
});

test('it sets a listed position and says whether that makes them an aide', function () {
    $therapist = therapistUser();
    $member = TeamMember::factory()->create(['user_id' => $therapist->id, 'position' => 'Occupational Therapist (OT)']);

    $exitCode = Artisan::call('team:set-position', [
        'email' => $therapist->email,
        'position' => 'Behavioural & Developmental Aide',
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('They are now an aide')
        ->and($member->refresh()->position)->toBe('Behavioural & Developmental Aide')
        ->and($therapist->refresh()->isAide())->toBeTrue();
});

test('it refuses a position that is not listed unless allowed', function () {
    $therapist = therapistUser();
    $member = TeamMember::factory()->create(['user_id' => $therapist->id, 'position' => 'Occupational Therapist (OT)']);

    expect(Artisan::call('team:set-position', ['email' => $therapist->email, 'position' => 'Behavioral Aide']))->toBe(1)
        ->and(Artisan::output())->toContain('is not a position listed')
        ->and($member->refresh()->position)->toBe('Occupational Therapist (OT)');

    expect(Artisan::call('team:set-position', [
        'email' => $therapist->email,
        'position' => 'Behavioral Aide',
        '--allow-other' => true,
    ]))->toBe(0)
        ->and($member->refresh()->position)->toBe('Behavioral Aide');
});

test('a dry run changes nothing', function () {
    $therapist = therapistUser();
    $member = TeamMember::factory()->create(['user_id' => $therapist->id, 'position' => 'Occupational Therapist (OT)']);

    Artisan::call('team:set-position', [
        'email' => $therapist->email,
        'position' => 'Behavioural & Developmental Aide',
        '--dry-run' => true,
    ]);

    expect(Artisan::output())->toContain('Would change')
        ->and($member->refresh()->position)->toBe('Occupational Therapist (OT)');
});

test('it fails for an unknown email or a user without a team profile', function () {
    expect(Artisan::call('team:set-position', [
        'email' => 'nobody@example.test',
        'position' => 'Occupational Therapist (OT)',
    ]))->toBe(1);

    expect(Artisan::call('team:set-position', [
        'email' => adminUser()->email,
        'position' => 'Occupational Therapist (OT)',
    ]))->toBe(1)
        ->and(Artisan::output())->toContain('has no team member profile');
});
