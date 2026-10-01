<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

/**
 * An account as it was before roles moved to Spatie: the `role` column is
 * set but no Spatie role has been granted.
 */
function legacyUser(string $role): User
{
    return User::withoutEvents(fn (): User => User::factory()->create(['role' => $role]));
}

test('it grants each user the spatie role matching their role column', function () {
    $admin = legacyUser('admin');
    $therapist = legacyUser('therapist');
    $client = legacyUser('client');

    expect($admin->isAdmin())->toBeFalse();

    $exitCode = Artisan::call('roles:transfer');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Transferred 1 admin user(s)')
        ->and($output)->toContain('Transferred 1 therapist user(s)')
        ->and($output)->toContain('Transferred 1 client user(s)')
        ->and($output)->toContain('Transferred 3 user(s), 0 already held their role');

    expect($admin->refresh()->getRoleNames()->all())->toBe(['admin'])
        ->and($therapist->refresh()->getRoleNames()->all())->toBe(['therapist'])
        ->and($client->refresh()->getRoleNames()->all())->toBe(['client'])
        ->and($admin->isAdmin())->toBeTrue();
});

test('it is safe to re-run and keeps roles granted on top of the primary one', function () {
    legacyUser('therapist');
    $both = User::factory()->admin()->create();
    $both->assignRole('therapist');

    Artisan::call('roles:transfer');
    Artisan::call('roles:transfer');

    expect(Artisan::output())->toContain('Transferred 0 user(s), 2 already held their role');
    expect($both->refresh()->getRoleNames()->sort()->values()->all())->toBe(['admin', 'therapist']);
});

test('it creates a role the column uses that does not exist yet', function () {
    $staff = legacyUser('staff');

    Artisan::call('roles:transfer');

    expect(Role::query()->where('name', 'staff')->exists())->toBeTrue()
        ->and($staff->refresh()->hasRole('staff'))->toBeTrue();
});

test('a dry run reports without granting anything', function () {
    $therapist = legacyUser('therapist');

    Artisan::call('roles:transfer', ['--dry-run' => true]);

    expect(Artisan::output())
        ->toContain('Would transfer 1 therapist user(s)')
        ->toContain('Would transfer 1 user(s), 0 already held their role');
    expect($therapist->refresh()->roles)->toBeEmpty();
});
