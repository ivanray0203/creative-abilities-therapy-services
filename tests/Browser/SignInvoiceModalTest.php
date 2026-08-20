<?php

use App\Models\Invoice;

/**
 * The signature pad has to put the ink exactly where the pointer is. That
 * holds only while the canvas's backing store matches its rendered box, so
 * these tests draw at known points and read the pixels back.
 */

/** A parent looking at an invoice of theirs that is waiting to be signed. */
function signableInvoice(): Invoice
{
    $client = clientWithUser();

    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'billed_by' => 'admin',
        'signed_invoice' => null,
    ]);

    test()->actingAs($client->user);

    return $invoice;
}

/**
 * Draws a horizontal stroke across the pad at the given height, then reports
 * the pixel under the middle of that stroke and one well clear of it.
 *
 * Coordinates are the pad's own drawing units. They are turned into pointer
 * coordinates through the canvas's bounding rect — the same rect the browser
 * reports pointer positions against — so the test asserts alignment rather
 * than assuming it.
 */
function drawOnSignaturePad(int $y): string
{
    return <<<JS
    (() => {
        const canvas = document.getElementById('signature-pad');
        const rect = canvas.getBoundingClientRect();

        const send = (type, x, y) => canvas.dispatchEvent(new PointerEvent(type, {
            bubbles: true,
            cancelable: true,
            pointerId: 1,
            pointerType: 'mouse',
            clientX: rect.left + (x / canvas.clientWidth) * rect.width,
            clientY: rect.top + (y / canvas.clientHeight) * rect.height,
        }));

        send('pointerdown', 40, {$y});
        send('pointermove', 90, {$y});
        send('pointermove', 140, {$y});
        send('pointerup', 140, {$y});

        const context = canvas.getContext('2d');
        const ratio = canvas.width / canvas.clientWidth;
        const at = (x, y) => Array.from(
            context.getImageData(Math.round(x * ratio), Math.round(y * ratio), 1, 1).data,
        );

        return {
            onStroke: at(90, {$y}),
            offStroke: at(90, {$y} + 60),
            backingWidth: canvas.width,
            expectedWidth: Math.round(canvas.clientWidth * (window.devicePixelRatio || 1)),
        };
    })()
    JS;
}

it('draws the stroke under the pointer, not offset from it', function () {
    $invoice = signableInvoice();

    $page = visit("/client/invoices/{$invoice->id}");
    $page->click('#open-sign-invoice');

    $result = $page->script(drawOnSignaturePad(40));

    // Ink where the pointer was...
    expect($result['onStroke'][0])->toBeLessThan(100)
        // ...and nowhere it was not.
        ->and($result['offStroke'][0])->toBe(255)
        // The alignment holds because the backing store matches the rendered
        // box: a pad sized to a fixed width drifts away from the cursor.
        ->and($result['backingWidth'])->toBe($result['expectedWidth']);

    $page->assertNoJavaScriptErrors();
});

it('keeps the ink aligned further down the pad', function () {
    $invoice = signableInvoice();

    $page = visit("/client/invoices/{$invoice->id}");
    $page->click('#open-sign-invoice');

    $result = $page->script(drawOnSignaturePad(120));

    expect($result['onStroke'][0])->toBeLessThan(100)
        ->and($result['offStroke'][0])->toBe(255);
});

it('clears the pad back to blank white', function () {
    $invoice = signableInvoice();

    $page = visit("/client/invoices/{$invoice->id}");
    $page->click('#open-sign-invoice');
    $page->script(drawOnSignaturePad(40));

    $page->click('#signature-pad + div button');

    $cleared = $page->script(<<<'JS'
    (() => {
        const canvas = document.getElementById('signature-pad');
        const context = canvas.getContext('2d');
        const ratio = canvas.width / canvas.clientWidth;

        return Array.from(context.getImageData(Math.round(90 * ratio), Math.round(40 * ratio), 1, 1).data);
    })()
    JS);

    expect($cleared[0])->toBe(255)
        ->and($cleared[3])->toBe(255);
});
