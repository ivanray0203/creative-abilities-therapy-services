<?php

use App\Console\Commands\ResetAdminPasswordsCommand;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('it resets every admin account password to the shared default and skips missing accounts', function () {
    $bryan = User::factory()->admin()->create(['email' => 'bryan.lerit@creativeabilitiestherapyservices.ca', 'password' => 'old-password']);
    $maryAnn = User::factory()->admin()->create(['email' => 'maryann.lerit@creativeabilitiestherapyservices.ca', 'password' => 'old-password']);

    $exitCode = Artisan::call('admins:reset-password');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)
        ->toContain('Reset password for Bryan Lerit <bryan.lerit@creativeabilitiestherapyservices.ca>.')
        ->toContain('Skipped Bernard Lerit <bernard.lerit@creativeabilitiestherapyservices.ca>: no account.')
        ->toContain('Reset 2, skipped 1 without an account');

    expect(Hash::check('Cats123*', $bryan->refresh()->password))->toBeTrue()
        ->and(Hash::check('Cats123*', $maryAnn->refresh()->password))->toBeTrue()
        ->and(Hash::check('old-password', $bryan->password))->toBeFalse()
        ->and(ResetAdminPasswordsCommand::PASSWORD)->toBe('Cats123*');
});

test('the reset password logs the admin in', function () {
    User::factory()->admin()->create(['email' => 'bryan.lerit@creativeabilitiestherapyservices.ca', 'password' => 'old-password']);

    Artisan::call('admins:reset-password');

    expect(auth()->attempt(['email' => 'bryan.lerit@creativeabilitiestherapyservices.ca', 'password' => 'Cats123*']))->toBeTrue();
});

test('a dry run reports without changing any password', function () {
    Mail::fake();
    $bryan = User::factory()->admin()->create(['email' => 'bryan.lerit@creativeabilitiestherapyservices.ca', 'password' => 'old-password']);
    $passwordBefore = $bryan->password;

    Artisan::call('admins:reset-password', ['--dry-run' => true]);

    expect(Artisan::output())
        ->toContain('Would reset password for Bryan Lerit <bryan.lerit@creativeabilitiestherapyservices.ca>.')
        ->toContain('Would reset 1, skipped 2 without an account');
    expect($bryan->refresh()->password)->toBe($passwordBefore);
});
