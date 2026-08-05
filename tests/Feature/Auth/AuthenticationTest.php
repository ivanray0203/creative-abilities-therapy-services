<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login screen can be rendered', function () {
    $this->get('/login')->assertStatus(200);
});

test('users can authenticate and are redirected by role', function (string $role, string $expectedRedirect) {
    $user = User::factory()->create(['role' => $role, 'password' => bcrypt('password')]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect($expectedRedirect);
})->with([
    ['admin', '/admin/intake'],
    ['therapist', '/therapist'],
    ['client', '/client/calendar'],
]);

test('users cannot authenticate with an invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('an authenticated user visiting the login page is redirected to their role home', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get('/login')->assertRedirect('/admin/intake');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
