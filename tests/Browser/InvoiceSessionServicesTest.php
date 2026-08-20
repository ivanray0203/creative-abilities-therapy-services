<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;

/**
 * Linking a session on the invoice form seeds the service lines from what
 * that visit actually delivered, priced from the service offering, instead
 * of leaving the biller to retype it.
 */
function invoiceableSession(array $serviceNames, array $prices): ScheduleSession
{
    $client = Client::factory()->create();

    $session = ScheduleSession::factory()->create([
        'client_id' => $client->id,
        'status' => 'completed',
    ]);

    foreach ($serviceNames as $index => $name) {
        $offering = ServiceOffering::factory()->create([
            'name' => $name,
            'base_price' => $prices[$index],
            'is_active' => true,
        ]);

        $clientService = ClientService::factory()->for($client)->create([
            'service_id' => $offering->id,
        ]);

        $session->clientServices()->attach($clientService->id);
    }

    return $session;
}

it('fills a service line from the linked session, priced from the offering', function () {
    $session = invoiceableSession(['Speech Therapy'], ['120.00']);

    $this->actingAs(adminUser());

    $page = visit('/admin/invoices/create');

    $page->click('#invoice-client')
        ->click("#invoice-client-{$session->client_id}")
        ->click('#invoice-session')
        ->click("#invoice-session-{$session->id}")
        // The trigger is a button, so the picked service shows as its label.
        ->assertSeeIn('#service-name-0', 'Speech Therapy')
        ->assertNoJavaScriptErrors();

    // The rate came from the offering, and the totals picked it up.
    $page->assertValue('#service-rate-0', '120.00')
        ->assertSee('$120.00');
});

it('adds one line per service when the visit covered several', function () {
    $session = invoiceableSession(
        ['Speech Therapy', 'Occupational Therapy'],
        ['120.00', '80.00'],
    );

    $this->actingAs(adminUser());

    $page = visit('/admin/invoices/create');

    $page->click('#invoice-client')
        ->click("#invoice-client-{$session->client_id}")
        ->click('#invoice-session')
        ->click("#invoice-session-{$session->id}");

    // Two lines, so a second Service Name field now exists.
    $page->assertPresent('#service-name-1')
        // Sub Total is 120 + 80, proving both rates landed.
        ->assertSee('$200.00')
        ->assertNoJavaScriptErrors();
});

it('leaves service lines the biller already filled in alone', function () {
    $session = invoiceableSession(['Speech Therapy'], ['120.00']);

    $this->actingAs(adminUser());

    $page = visit('/admin/invoices/create');

    $page->click('#invoice-client')
        ->click("#invoice-client-{$session->client_id}")
        // A rate typed before the session is linked is the biller's own work.
        ->fill('#service-rate-0', '999')
        ->click('#invoice-session')
        ->click("#invoice-session-{$session->id}");

    $page->assertValue('#service-rate-0', '999')
        ->assertNoJavaScriptErrors();
});
