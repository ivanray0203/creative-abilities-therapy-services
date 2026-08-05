<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('forgot password screen can be rendered', function () {
    $this->get('/forgot-password')->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) {
        $this->get('/reset-password/'.$notification->token)->assertStatus(200);

        return true;
    });
});

test('password can be reset with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/login');

        expect($user->fresh()->password)->not->toBe($user->password);

        return true;
    });
});

test('self-service change password requires the current password', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $response = $this->actingAs($user)->put('/password', [
        'current_password' => 'wrong-current-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('current_password');
});
