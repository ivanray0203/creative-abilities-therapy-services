<?php

use App\Models\Client;
use App\Models\Intake;
use App\Models\InvoiceService;
use App\Models\ScheduleSession;
use App\Models\TeamMember;
use App\Models\User;

/**
 * A client billable from the invoice form: the picker only offers clients
 * with a delivered session behind them.
 */
function billableClientFor(User $therapist, ?string $fundingSource = null): Client
{
    $intake = Intake::factory()->create(['funding_source' => $fundingSource]);
    $client = Client::factory()->create(['original_intake_id' => $intake->id]);

    ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $therapist->id,
        'status' => 'confirmed',
    ]);

    return $client;
}

test('the invoice form offers the rate card, not the service offering catalog', function () {
    $therapist = therapistUser();
    billableClientFor($therapist);
    $line = InvoiceService::factory()->code('ot-documentation')->create();
    InvoiceService::factory()->code('pt-documentation')->create(['is_active' => false]);

    $this->actingAs(adminUser())->get('/admin/invoices/create')
        ->assertInertia(fn ($page) => $page
            ->has('services', 1)
            ->where('services.0.id', $line->id)
            ->where('services.0.name', 'OT - Documentation')
            ->where('services.0.rate_fscd', '94.76')
            ->where('services.0.rate_private', '142.14')
        );
});

test('a therapist bills their own rates, falling back to the published rate', function () {
    $therapist = therapistUser();
    billableClientFor($therapist);

    $overridden = InvoiceService::factory()
        ->code('occupational-therapist-home-visit')
        ->create(['sort_order' => 1]);
    $untouched = InvoiceService::factory()->code('ot-documentation')->create(['sort_order' => 2]);

    $teamMember = TeamMember::factory()->create(['user_id' => $therapist->id]);
    $teamMember->invoiceServiceRates()->attach($overridden->id, ['rate_private' => '175.00']);

    $this->actingAs($therapist)->get('/therapist/invoices/create')
        ->assertInertia(fn ($page) => $page
            // Overridden on the private column only; FSCD still the card rate.
            ->where('services.0.id', $overridden->id)
            ->where('services.0.rate_private', '175.00')
            ->where('services.0.rate_fscd', '94.76')
            // No override at all, so both rates come straight off the card.
            ->where('services.1.id', $untouched->id)
            ->where('services.1.rate_fscd', '94.76')
            ->where('services.1.rate_private', '142.14')
        );
});

test('an admin never picks up a therapist rate override', function () {
    $therapist = therapistUser();
    billableClientFor($therapist);
    $line = InvoiceService::factory()->code('psychologist-home-visit')->create();

    $teamMember = TeamMember::factory()->create(['user_id' => $therapist->id]);
    $teamMember->invoiceServiceRates()->attach($line->id, [
        'rate_fscd' => '500.00',
        'rate_private' => '600.00',
    ]);

    $this->actingAs(adminUser())->get('/admin/invoices/create')
        ->assertInertia(fn ($page) => $page
            ->where('services.0.rate_fscd', '98.37')
            ->where('services.0.rate_private', '98.37')
        );
});

test('the client carries its funding source so the form can price the line', function () {
    $therapist = therapistUser();
    $fscd = billableClientFor($therapist, 'BDS-FSCD');
    InvoiceService::factory()->code('slp-documentation')->create();

    $this->actingAs($therapist)->get('/therapist/invoices/create')
        ->assertInertia(fn ($page) => $page
            ->where('clients.0.id', $fscd->id)
            ->where('clients.0.original_intake.funding_source', 'BDS-FSCD')
        );
});

test('FSCD funding sources bill the FSCD column and everything else bills private', function () {
    expect(InvoiceService::fundingStream('BDS-FSCD'))->toBe('fscd');
    expect(InvoiceService::fundingStream('SS-FSCD'))->toBe('fscd');
    expect(InvoiceService::fundingStream('Counselling-FSCD'))->toBe('fscd');
    expect(InvoiceService::fundingStream('Insurance'))->toBe('private');
    expect(InvoiceService::fundingStream('private'))->toBe('private');
    expect(InvoiceService::fundingStream(null))->toBe('private');
});
