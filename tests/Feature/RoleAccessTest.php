<?php

use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\InvoiceController;
use App\Models\Complaint;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

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

/**
 * Phase 18 — destructive actions used to rely entirely on which route group
 * they were wired into. These assert the policies deny them regardless.
 *
 * Routing already keeps non-admins out of /admin/*, so the checks below call
 * the policy directly: that is the layer that has to hold if a route is ever
 * added elsewhere.
 *
 * @see tasks/18-scheduling-conflicts-review-queue-authorization.md
 */
test('only an admin may delete or mark an invoice paid', function (string $ability) {
    $invoice = Invoice::factory()->create();
    $therapist = therapistUser();

    // Even the therapist the invoice belongs to may not do these.
    $invoice->update(['therapist_id' => $therapist->id]);

    expect(adminUser()->can($ability, $invoice))->toBeTrue();
    expect($therapist->can($ability, $invoice))->toBeFalse();
    expect(clientWithUser()->user->can($ability, $invoice))->toBeFalse();
})->with(['delete', 'markPaid']);

test('only an admin may review or resolve a complaint', function () {
    $therapist = therapistUser();
    $complaint = Complaint::factory()->create(['therapist_id' => $therapist->id]);

    expect(adminUser()->can('review', $complaint))->toBeTrue();
    // The therapist named in the complaint must not close it themselves.
    expect($therapist->can('review', $complaint))->toBeFalse();
    expect(clientWithUser()->user->can('review', $complaint))->toBeFalse();
});

test('a therapist may only manage their own sessions', function () {
    $owner = therapistUser();
    $intruder = therapistUser();
    $session = ScheduleSession::factory()->create(['therapist_id' => $owner->id]);

    expect($owner->can('manage', $session))->toBeTrue();
    expect($intruder->can('manage', $session))->toBeFalse();
    expect(adminUser()->can('manage', $session))->toBeTrue();
    expect(clientWithUser()->user->can('manage', $session))->toBeFalse();
});

test('only the child\'s own parent may verify a session', function () {
    $client = clientWithUser();
    $stranger = clientWithUser();
    $session = ScheduleSession::factory()->create(['client_id' => $client->id]);

    expect($client->user->can('verify', $session))->toBeTrue();
    expect($stranger->user->can('verify', $session))->toBeFalse();
    expect(therapistUser()->can('verify', $session))->toBeFalse();
});

test('deleting an invoice is refused for a non-admin even when routed directly', function () {
    $therapist = therapistUser();
    $invoice = Invoice::factory()->create(['therapist_id' => $therapist->id]);

    // Registered here rather than in web.php: the point is that the guard
    // holds even when a route bypasses the admin group entirely.
    Route::middleware(['web', 'auth'])
        ->delete('__test__/invoices/{invoice}', [InvoiceController::class, 'destroy']);

    $this->actingAs($therapist)
        ->delete("/__test__/invoices/{$invoice->id}")
        ->assertNotFound();

    expect(Invoice::find($invoice->id))->not->toBeNull();
});

test('resolving a complaint is refused for a non-admin even when routed directly', function () {
    $therapist = therapistUser();
    $complaint = Complaint::factory()->create(['therapist_id' => $therapist->id, 'status' => 'open']);

    Route::middleware(['web', 'auth'])
        ->post('__test__/complaints/{complaint}/resolve', [ComplaintController::class, 'resolve']);

    $this->actingAs($therapist)
        ->post("/__test__/complaints/{$complaint->id}/resolve", ['admin_response' => 'closing my own case'])
        ->assertNotFound();

    expect($complaint->refresh()->status)->toBe('open');
});
