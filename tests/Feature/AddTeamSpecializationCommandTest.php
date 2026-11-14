<?php

use App\Models\ServiceOffering;
use App\Models\TeamMember;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    ServiceOffering::factory()->create(['name' => 'Behavioural Aide Services']);
    ServiceOffering::factory()->create(['name' => 'Respite Aide Services']);
});

test('it adds a specialization to a team member found by email', function () {
    $aide = therapistUser();
    $member = TeamMember::factory()->create(['user_id' => $aide->id, 'specializations' => null]);

    $exitCode = Artisan::call('team:add-specialization', [
        'email' => $aide->email,
        'specialization' => ['Behavioural Aide Services'],
    ]);

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('Added to')
        ->and($member->refresh()->specializations)->toBe(['Behavioural Aide Services']);
});

test('it keeps existing specializations and skips duplicates', function () {
    $aide = therapistUser();
    $member = TeamMember::factory()->create(['user_id' => $aide->id, 'specializations' => ['Respite Aide Services']]);

    Artisan::call('team:add-specialization', [
        'email' => $aide->email,
        'specialization' => ['Behavioural Aide Services', 'Respite Aide Services'],
    ]);
    Artisan::call('team:add-specialization', [
        'email' => $aide->email,
        'specialization' => ['Behavioural Aide Services'],
    ]);

    expect(Artisan::output())->toContain('already has')
        ->and($member->refresh()->specializations)->toBe(['Respite Aide Services', 'Behavioural Aide Services']);
});

test('it rejects a name that is not a service', function () {
    $aide = therapistUser();
    $member = TeamMember::factory()->create(['user_id' => $aide->id, 'specializations' => null]);

    $exitCode = Artisan::call('team:add-specialization', [
        'email' => $aide->email,
        'specialization' => ['Behavioural & Developmental Aide'],
    ]);

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('Not a service name: Behavioural & Developmental Aide')
        ->and($member->refresh()->specializations)->toBeNull();
});

test('a dry run changes nothing', function () {
    $aide = therapistUser();
    $member = TeamMember::factory()->create(['user_id' => $aide->id, 'specializations' => null]);

    Artisan::call('team:add-specialization', [
        'email' => $aide->email,
        'specialization' => ['Behavioural Aide Services'],
        '--dry-run' => true,
    ]);

    expect(Artisan::output())->toContain('Would add')
        ->and($member->refresh()->specializations)->toBeNull();
});

test('it fails for an unknown email or a user without a team profile', function () {
    expect(Artisan::call('team:add-specialization', [
        'email' => 'nobody@example.test',
        'specialization' => ['Behavioural Aide Services'],
    ]))->toBe(1);

    expect(Artisan::call('team:add-specialization', [
        'email' => adminUser()->email,
        'specialization' => ['Behavioural Aide Services'],
    ]))->toBe(1)
        ->and(Artisan::output())->toContain('has no team member profile');
});
