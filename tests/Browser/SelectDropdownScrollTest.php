<?php

use App\Models\Client;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;

/**
 * A long Select — the invoice form's service picker is the one that surfaced
 * this — looked unscrollable: Radix scrolls its viewport but injects a
 * stylesheet hiding the scrollbar, so the only affordance was a chevron that
 * responds to hover.
 *
 * These read computed geometry rather than pixels because headless Chromium
 * draws overlay scrollbars that never appear in a screenshot.
 */
const VIEWPORT_PROBE = <<<'JS'
    (() => {
        const viewport = document.querySelector('[data-radix-select-viewport]');
        const content = viewport.parentElement;

        return {
            scrollable: viewport.scrollHeight > viewport.clientHeight,
            overflowY: getComputedStyle(viewport).overflowY,
            scrollbarWidth: getComputedStyle(viewport).scrollbarWidth,
            fitsInWindow:
                content.getBoundingClientRect().bottom <= window.innerHeight,
        };
    })()
JS;

it('leaves a long service list scrollable with a visible scrollbar', function () {
    ServiceOffering::factory()->count(40)->create(['is_active' => true]);

    $this->actingAs(therapistUser());

    $page = visit('/therapist/invoices/create');
    $page->click('#service-name-0');

    $probe = $page->script(VIEWPORT_PROBE);

    expect($probe['scrollable'])->toBeTrue()
        ->and($probe['overflowY'])->toBe('auto')
        // Radix's own rule sets this to `none`, which is what made the list
        // look like it could not be scrolled at all.
        ->and($probe['scrollbarWidth'])->not->toBe('none');
});

it('keeps the dropdown inside a short window instead of running off the bottom', function () {
    ServiceOffering::factory()->count(40)->create(['is_active' => true]);

    $this->actingAs(therapistUser());

    $page = visit('/therapist/invoices/create');
    $page->resize(1280, 500);
    $page->click('#service-name-0');

    $probe = $page->script(VIEWPORT_PROBE);

    expect($probe['fitsInWindow'])->toBeTrue()
        ->and($probe['scrollable'])->toBeTrue();
});

/**
 * The fix lives in the shared Select primitive rather than the service
 * field, so every other dropdown on the page inherits it. The client picker
 * is the other long one on this form.
 */
it('leaves a long client list scrollable with a visible scrollbar', function () {
    // A client only reaches the picker once it has a delivered session
    // (InvoiceController::clientOptions).
    Client::factory()->count(40)->create()->each(
        fn (Client $client) => ScheduleSession::factory()->create([
            'client_id' => $client->id,
            'status' => 'completed',
        ]),
    );

    $this->actingAs(adminUser());

    $page = visit('/admin/invoices/create');
    $page->click('#invoice-client');

    $probe = $page->script(VIEWPORT_PROBE);

    expect($probe['scrollable'])->toBeTrue()
        ->and($probe['overflowY'])->toBe('auto')
        ->and($probe['scrollbarWidth'])->not->toBe('none');
});

it('keeps the client dropdown inside a short window', function () {
    // A client only reaches the picker once it has a delivered session
    // (InvoiceController::clientOptions).
    Client::factory()->count(40)->create()->each(
        fn (Client $client) => ScheduleSession::factory()->create([
            'client_id' => $client->id,
            'status' => 'completed',
        ]),
    );

    $this->actingAs(adminUser());

    $page = visit('/admin/invoices/create');
    $page->resize(1280, 500);
    $page->click('#invoice-client');

    $probe = $page->script(VIEWPORT_PROBE);

    expect($probe['fitsInWindow'])->toBeTrue()
        ->and($probe['scrollable'])->toBeTrue();
});

it('still opens a short list without a scrollbar', function () {
    ServiceOffering::factory()->count(3)->create(['is_active' => true]);

    $this->actingAs(therapistUser());

    $page = visit('/therapist/invoices/create');
    $page->click('#service-name-0');

    $probe = $page->script(VIEWPORT_PROBE);

    expect($probe['scrollable'])->toBeFalse()
        ->and($probe['fitsInWindow'])->toBeTrue();
});
