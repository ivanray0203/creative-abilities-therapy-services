<?php

/**
 * The tab icon shipped as Laravel's default long after the clinic had its
 * own, because the brand assets live in `public/CatsLogo` while browsers
 * ask for the root paths.
 */
test('the root browser icons are the clinic logo', function (string $icon) {
    expect(file_get_contents(public_path($icon)))
        ->toBe(file_get_contents(public_path("CatsLogo/{$icon}")));
})->with([
    'favicon.ico',
    'favicon.svg',
    'favicon-96x96.png',
    'apple-touch-icon.png',
]);

test('the page head points at every icon size', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('<link rel="icon" href="/favicon.ico" sizes="any">', false)
        ->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml">', false)
        ->assertSee('<link rel="icon" href="/favicon-96x96.png" type="image/png" sizes="96x96">', false)
        ->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">', false);
});
