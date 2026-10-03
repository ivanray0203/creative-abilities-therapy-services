<?php

use App\Models\BillingItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\TeamMember;
use App\Models\User;

/**
 * Someone who is both an admin and a therapist, admin being the role they
 * log in to.
 */
function adminTherapistUser(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole('therapist');
    TeamMember::factory()->create(['user_id' => $user->id]);

    return $user;
}

test('a single-role user is offered no other portal', function () {
    $this->actingAs(adminUser())->get('/admin/intake')
        ->assertInertia(fn ($page) => $page->where('auth.portals', []));
});

test('a user with two roles is offered the portal they are not in', function () {
    $user = adminTherapistUser();

    $this->actingAs($user)->get('/admin/intake')
        ->assertInertia(fn ($page) => $page->where('auth.portals', [['role' => 'therapist', 'url' => '/therapist']]));

    $this->actingAs($user)->get('/therapist')
        ->assertInertia(fn ($page) => $page->where('auth.portals', [['role' => 'admin', 'url' => '/admin/intake']]));
});

test('the shared pages act in the role of the portal being visited', function () {
    $user = adminTherapistUser();
    $ownInvoice = Invoice::factory()->create(['therapist_id' => $user->id, 'billed_by' => 'therapist']);
    $otherTherapistInvoice = Invoice::factory()->create(['therapist_id' => therapistUser()->id, 'billed_by' => 'therapist']);
    $clinicInvoice = Invoice::factory()->create(['billed_by' => 'admin']);

    $this->actingAs($user)->get('/therapist/invoices')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('role', 'therapist')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $ownInvoice->id)
        );

    $this->actingAs($user)->get('/admin/invoices')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('role', 'admin')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $clinicInvoice->id)
        );

    // In the therapist portal they are a therapist and nothing more.
    $this->actingAs($user)->get("/therapist/invoices/{$otherTherapistInvoice->id}")->assertNotFound();
    $this->actingAs($user)->get("/therapist/invoices/{$clinicInvoice->id}")->assertNotFound();
});

test('a bill raised in the therapist portal stays on the therapist side of the ledger', function () {
    $user = adminTherapistUser();
    $client = Client::factory()->create();

    $this->actingAs($user)->post('/therapist/billing', [
        'client_id' => $client->id,
        'services' => [['invoice_service_id' => null, 'name' => 'Home Visit', 'quantity' => 1, 'rate' => 100]],
    ])->assertRedirect('/therapist/billing')->assertSessionHasNoErrors();

    $ownBill = BillingItem::query()->sole();
    expect($ownBill->billed_by)->toBe('therapist')
        ->and($ownBill->therapist_id)->toBe($user->id);

    $this->actingAs($user)->post('/admin/billing', [
        'client_id' => $client->id,
        'therapist_id' => therapistUser()->id,
        'services' => [['invoice_service_id' => null, 'name' => 'Documentation', 'quantity' => 1, 'rate' => 50]],
    ])->assertRedirect('/admin/billing')->assertSessionHasNoErrors();

    $clinicBill = BillingItem::query()->where('billed_by', 'admin')->sole();

    $this->actingAs($user)->get('/therapist/billing')
        ->assertInertia(fn ($page) => $page->has('items.data', 1)->where('items.data.0.id', $ownBill->id));

    $this->actingAs($user)->get('/admin/billing')
        ->assertInertia(fn ($page) => $page->has('items.data', 1)->where('items.data.0.id', $clinicBill->id));
});

test('existing billing items are put on the side their issuer was on', function () {
    $migration = require database_path('migrations/2026_10_01_061036_add_billed_by_to_billing_items_table.php');
    $clinicBill = BillingItem::factory()->create(['issued_by_id' => adminUser()->id]);
    $therapistBill = BillingItem::factory()->create();

    $migration->down();
    $migration->up();

    expect($clinicBill->refresh()->billed_by)->toBe('admin')
        ->and($therapistBill->refresh()->billed_by)->toBe('therapist');
});
