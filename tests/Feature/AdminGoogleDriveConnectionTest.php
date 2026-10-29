<?php

use App\Models\PendingDriveUpload;
use App\Models\User;

beforeEach(function () {
    config()->set('services.google_drive.client_id', 'test-client-id');
    config()->set('services.google_drive.client_secret', 'test-client-secret');
});

test('the connect route sends an admin to Google with offline access so a refresh token comes back', function () {
    $response = $this->actingAs(adminUser())->get('/admin/google-drive/connect');

    $response->assertRedirectContains('accounts.google.com');

    $target = $response->headers->get('Location');

    expect($target)->toContain('access_type=offline')
        ->and($target)->toContain('prompt=consent')
        ->and($target)->toContain(urlencode(route('admin.google-drive.callback')));
});

test('connecting without credentials configured says so instead of sending the admin to Google', function () {
    config()->set('services.google_drive.client_id', null);

    $this->actingAs(adminUser())->get('/admin/google-drive/connect')
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('error', fn ($error) => str_contains($error, 'GOOGLE_DRIVE_CLIENT_ID'));
});

test('opening the callback directly explains the flow was never started', function () {
    $this->actingAs(adminUser())->get('/admin/google-drive/callback')
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('error', fn ($error) => str_contains($error, 'did not start'));
});

test('a denial from Google surfaces the reason Google gave', function () {
    $this->actingAs(adminUser())->get('/admin/google-drive/callback?error=access_denied')
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('error', fn ($error) => str_contains($error, 'access_denied'));
});

test('the callback is closed to guests and to non-admins', function () {
    $this->get('/admin/google-drive/callback')->assertRedirect('/login');

    $this->actingAs(therapistUser())->get('/admin/google-drive/callback')->assertRedirect('/therapist');
});

function driveOwner(): User
{
    return User::factory()->admin()->create(['email' => 'Drive.Owner@example.com']);
}

test('the Drive owner is told when Google Drive still needs connecting, with the uploads waiting on it', function () {
    config()->set('services.google_drive.owner_email', 'drive.owner@example.com');
    config()->set('services.google_drive.shared_drive_id', 'root-folder');
    config()->set('services.google_drive.token_path', storage_path('framework/testing/no-token.json'));
    PendingDriveUpload::factory()->count(2)->create();
    PendingDriveUpload::factory()->synced()->create();

    $this->actingAs(driveOwner())->get('/admin')
        ->assertInertia(fn ($page) => $page->where('googleDrive', [
            'configured' => true,
            'connected' => false,
            'pending_uploads' => 2,
        ]));
});

test('the prompt goes away once Google Drive is connected', function () {
    $tokenPath = storage_path('framework/testing/drive-token-'.uniqid().'.json');
    file_put_contents($tokenPath, '{}');
    config()->set('services.google_drive.shared_drive_id', 'root-folder');
    config()->set('services.google_drive.token_path', $tokenPath);
    config()->set('services.google_drive.owner_email', 'drive.owner@example.com');

    try {
        $this->actingAs(driveOwner())->get('/admin')
            ->assertInertia(fn ($page) => $page->where('googleDrive.connected', true));
    } finally {
        unlink($tokenPath);
    }
});

test('only the Drive owner gets the Google Drive status', function () {
    config()->set('services.google_drive.owner_email', 'drive.owner@example.com');

    $this->actingAs(adminUser())->get('/admin')
        ->assertInertia(fn ($page) => $page->where('googleDrive', null));

    $this->actingAs(therapistUser())->get('/therapist')
        ->assertInertia(fn ($page) => $page->where('googleDrive', null));
});
