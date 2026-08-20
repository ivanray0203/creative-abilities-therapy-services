<?php

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
