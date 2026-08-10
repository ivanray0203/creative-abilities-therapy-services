<?php

use App\Models\Client;
use App\Models\Intake;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use App\Models\User;

/**
 * Phase 17 — one parent, several children, each its own Client record.
 *
 * @see tasks/17-multi-child-client-model.md
 */

/**
 * A parent User with two children in care, mirroring two approved intakes.
 *
 * @return array{0: User, 1: Client, 2: Client}
 */
function parentWithTwoChildren(): array
{
    $parent = User::factory()->client()->create(['email' => 'parent@example.com']);

    $firstBorn = Client::factory()->create([
        'user_id' => $parent->id,
        'original_intake_id' => Intake::factory()->create(['child_first_name' => 'Ivan', 'child_last_name' => 'Reyes'])->id,
    ]);

    $secondBorn = Client::factory()->create([
        'user_id' => $parent->id,
        'original_intake_id' => Intake::factory()->create(['child_first_name' => 'Maria', 'child_last_name' => 'Reyes'])->id,
    ]);

    return [$parent, $firstBorn, $secondBorn];
}

test('one parent can hold several client records', function () {
    [$parent, $firstBorn, $secondBorn] = parentWithTwoChildren();

    expect($parent->clientProfiles()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$firstBorn->id, $secondBorn->id])->sort()->values()->all());
});

test('the portal defaults to the first child and lists every child for the switcher', function () {
    [$parent, $firstBorn] = parentWithTwoChildren();

    $this->actingAs($parent)->get('/client/calendar')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.client_id', $firstBorn->id)
            ->has('auth.children', 2)
            ->where('auth.children.0.name', 'Ivan Reyes')
            ->where('auth.children.1.name', 'Maria Reyes')
        );
});

test('switching child rescopes the calendar', function () {
    [$parent, $firstBorn, $secondBorn] = parentWithTwoChildren();

    $firstBornSession = ScheduleSession::factory()->create(['client_id' => $firstBorn->id]);
    $secondBornSession = ScheduleSession::factory()->create(['client_id' => $secondBorn->id]);

    $this->actingAs($parent)->get('/client/calendar')
        ->assertInertia(fn ($page) => $page
            ->has('sessions', 1)
            ->where('sessions.0.id', $firstBornSession->id)
        );

    $this->actingAs($parent)
        ->post('/client/select-child', ['client_id' => $secondBorn->id])
        ->assertSessionHasNoErrors();

    $this->actingAs($parent)->get('/client/calendar')
        ->assertInertia(fn ($page) => $page
            ->has('sessions', 1)
            ->where('sessions.0.id', $secondBornSession->id)
        );
});

test('a parent cannot switch to a child who is not theirs', function () {
    [$parent, $firstBorn] = parentWithTwoChildren();
    $stranger = clientWithUser();

    $this->actingAs($parent)
        ->post('/client/select-child', ['client_id' => $stranger->id])
        ->assertNotFound();

    // The selection is left untouched rather than silently cleared.
    $this->actingAs($parent)->get('/client/calendar')
        ->assertInertia(fn ($page) => $page->where('auth.client_id', $firstBorn->id));
});

test('a parent can act on a session belonging to a child who is not currently selected', function () {
    [$parent, , $secondBorn] = parentWithTwoChildren();

    // The portal is still scoped to the first child.
    $session = ScheduleSession::factory()->create([
        'client_id' => $secondBorn->id,
        'status' => 'pending',
    ]);

    $this->actingAs($parent)
        ->post("/client/sessions/{$session->id}/verify")
        ->assertSessionHasNoErrors();

    expect($session->refresh()->status)->toBe('confirmed');
});

test('a parent can view an invoice for a child who is not currently selected', function () {
    [$parent, , $secondBorn] = parentWithTwoChildren();

    $invoice = Invoice::factory()->create(['client_id' => $secondBorn->id]);

    $this->actingAs($parent)->get("/client/invoices/{$invoice->id}")->assertOk();
});

test('a parent cannot view another family\'s invoice', function () {
    [$parent] = parentWithTwoChildren();
    $stranger = clientWithUser();

    $invoice = Invoice::factory()->create(['client_id' => $stranger->id]);

    $this->actingAs($parent)->get("/client/invoices/{$invoice->id}")->assertNotFound();
});

test('the invoice list shows only the selected child', function () {
    [$parent, $firstBorn, $secondBorn] = parentWithTwoChildren();

    $firstBornInvoice = Invoice::factory()->create(['client_id' => $firstBorn->id]);
    Invoice::factory()->create(['client_id' => $secondBorn->id]);

    $this->actingAs($parent)->get('/client/invoices')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $firstBornInvoice->id)
        );
});
