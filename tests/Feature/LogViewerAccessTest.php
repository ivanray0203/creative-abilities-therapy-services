<?php

test('an admin can open the log viewer', function () {
    $this->actingAs(adminUser())->get('/admin/log-viewer')->assertOk();
    $this->actingAs(adminUser())->getJson('/admin/log-viewer/api/files')->assertOk();
});

test('guests are sent to the login page', function () {
    $this->get('/admin/log-viewer')->assertRedirect('/login');
});

test('therapists and clients are turned away from the log viewer and its api', function () {
    foreach ([therapistUser(), clientWithUser()->user] as $user) {
        $this->actingAs($user)->get('/admin/log-viewer')->assertRedirect();
        expect($this->actingAs($user)->getJson('/admin/log-viewer/api/files')->status())->toBeIn([302, 403]);
    }
});

test('the log viewer stays admin-only in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->actingAs(adminUser())->get('/admin/log-viewer')->assertOk();
    $this->actingAs(therapistUser())->get('/admin/log-viewer')->assertRedirect();
});
