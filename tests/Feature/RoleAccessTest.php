<?php

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to login from role-guarded routes', function (string $path) {
    $this->get($path)->assertRedirect('/login');
})->with(['/admin/intake', '/therapist', '/client/calendar']);

test('a role can access its own home but is redirected away from other roles homes', function (string $role, string $ownHome, array $otherHomes) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get($ownHome)->assertStatus(200);

    foreach ($otherHomes as $otherHome => $expectedRedirect) {
        $this->actingAs($user)->get($otherHome)->assertRedirect($expectedRedirect);
    }
})->with([
    ['admin', '/admin/intake', ['/therapist' => '/admin/intake', '/client/calendar' => '/admin/intake']],
    ['therapist', '/therapist', ['/admin/intake' => '/therapist', '/client/calendar' => '/therapist']],
    ['client', '/client/calendar', ['/admin/intake' => '/client/calendar', '/therapist' => '/client/calendar']],
]);

test('shared inertia auth props expose user, team member, and client id', function () {
    $therapist = User::factory()->therapist()->create();
    TeamMember::factory()->create(['user_id' => $therapist->id]);

    $response = $this->actingAs($therapist)->get('/therapist');

    $response->assertInertia(fn ($page) => $page
        ->where('auth.user.id', $therapist->id)
        ->where('auth.team_member.user_id', $therapist->id)
        ->where('auth.client_id', null)
    );
});
