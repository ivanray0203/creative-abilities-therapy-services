<?php

use App\Console\Commands\CreateAdminAccountsCommand;
use App\Mail\StaffInviteMail;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('it creates the admin accounts with their roles and emails each a temporary password', function () {
    Mail::fake();

    $exitCode = Artisan::call('admins:create');

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('Created 3, skipped 0 existing');

    $bryan = User::query()->where('email', 'bryan.lerit@creativeabilitiestherapyservices.ca')->firstOrFail();
    $bernard = User::query()->where('email', 'bernard.lerit@creativeabilitiestherapyservices.ca')->firstOrFail();
    $maryAnn = User::query()->where('email', 'maryann.lerit@creativeabilitiestherapyservices.ca')->firstOrFail();

    expect($bryan->getRoleNames()->all())->toBe(['admin'])
        ->and($bryan->first_name)->toBe('Bryan')
        ->and($bryan->is_active)->toBeTrue()
        ->and($bryan->teamMember)->toBeNull()
        ->and($bernard->getRoleNames()->all())->toBe(['admin']);

    expect($maryAnn->role)->toBe('admin')
        ->and($maryAnn->getRoleNames()->sort()->values()->all())->toBe(['admin', 'therapist'])
        ->and($maryAnn->first_name)->toBe('Mary Ann')
        ->and($maryAnn->teamMember->position)->toBe('Occupational Therapist (OT)')
        ->and($maryAnn->teamMember->employment_status)->toBe('active');

    Mail::assertQueued(StaffInviteMail::class, 3);
    Mail::assertQueued(StaffInviteMail::class, function (StaffInviteMail $mail) use ($maryAnn) {
        return $mail->hasTo($maryAnn->email)
            && $mail->firstName === 'Mary Ann'
            && strlen($mail->password) === 10
            && Hash::check($mail->password, $maryAnn->password);
    });
});

test('the account holding both roles can reach the admin and therapist portals', function () {
    Mail::fake();

    Artisan::call('admins:create');

    $maryAnn = User::query()->where('email', 'maryann.lerit@creativeabilitiestherapyservices.ca')->firstOrFail();

    $this->actingAs($maryAnn)->get('/admin/intake')->assertStatus(200);
    $this->actingAs($maryAnn)->get('/therapist')->assertStatus(200);
});

test('an existing account keeps its password, gets no email, and is granted only its missing roles', function () {
    Mail::fake();

    $bernard = User::factory()->admin()->create(['email' => 'bernard.lerit@creativeabilitiestherapyservices.ca']);
    $maryAnn = User::factory()->therapist()->create(['email' => 'maryann.lerit@creativeabilitiestherapyservices.ca']);
    $passwordBefore = $maryAnn->password;

    Artisan::call('admins:create');
    $output = Artisan::output();

    expect($output)
        ->toContain('Skipped Bernard Lerit <bernard.lerit@creativeabilitiestherapyservices.ca>: already has an account.')
        ->toContain('already has an account, granted admin.')
        ->toContain('Created 1, skipped 2 existing');

    $maryAnn->refresh();
    expect($maryAnn->password)->toBe($passwordBefore)
        ->and($maryAnn->role)->toBe('therapist')
        ->and($maryAnn->getRoleNames()->sort()->values()->all())->toBe(['admin', 'therapist'])
        ->and(TeamMember::query()->where('user_id', $maryAnn->id)->exists())->toBeTrue()
        ->and($bernard->refresh()->getRoleNames()->all())->toBe(['admin']);

    Mail::assertQueued(StaffInviteMail::class, 1);
    Mail::assertQueued(StaffInviteMail::class, fn (StaffInviteMail $mail) => $mail->hasTo('bryan.lerit@creativeabilitiestherapyservices.ca'));

    Artisan::call('admins:create');
    expect(Artisan::output())->toContain('Created 0, skipped 3 existing');
    Mail::assertQueued(StaffInviteMail::class, 1);
});

test('a dry run reports without creating anything or sending mail', function () {
    Mail::fake();

    Artisan::call('admins:create', ['--dry-run' => true]);

    expect(Artisan::output())
        ->toContain('Would create Mary Ann Lerit <maryann.lerit@creativeabilitiestherapyservices.ca> as admin + therapist.')
        ->toContain('Would create 3, skipped 0 existing');
    expect(User::query()->count())->toBe(0);
    expect(TeamMember::query()->count())->toBe(0);
    Mail::assertNothingOutgoing();
});

test('the command lists exactly the three clinic administrators', function () {
    expect(array_column(CreateAdminAccountsCommand::ACCOUNTS, 'roles', 'email'))->toBe([
        'bryan.lerit@creativeabilitiestherapyservices.ca' => ['admin'],
        'bernard.lerit@creativeabilitiestherapyservices.ca' => ['admin'],
        'maryann.lerit@creativeabilitiestherapyservices.ca' => ['admin', 'therapist'],
    ]);
});
