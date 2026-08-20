<?php

/**
 * Facebook, Messenger, Instagram and X all fetch the page with a crawler
 * that runs no JavaScript, so the preview tags have to be in the Blade
 * shell — an Inertia <Head> would be invisible to them.
 */
test('the page carries the open graph and twitter card tags', function () {
    $response = $this->get('/');

    $response->assertOk();

    foreach ([
        '<meta property="og:site_name" content="Creative Abilities Therapy Services">',
        '<meta property="og:type" content="website">',
        '<meta property="og:locale" content="en_CA">',
        '<meta name="twitter:card" content="summary_large_image">',
    ] as $tag) {
        $response->assertSee($tag, false);
    }

    foreach (['og:title', 'og:description', 'og:image', 'twitter:title', 'twitter:description', 'twitter:image'] as $property) {
        expect($response->getContent())->toContain($property);
    }
});

/**
 * A relative URL is the usual reason a preview comes back blank — every
 * crawler requires the image and canonical URL to be absolute.
 */
test('the shared url and image are absolute', function () {
    $content = $this->get('/')->getContent();

    expect($content)
        ->toContain('<meta property="og:url" content="'.url('/').'">')
        ->toContain('<meta property="og:image" content="'.url('/images/1920x1080/photo_playing_1920x1080.jpg').'">')
        ->toContain('<meta name="twitter:image" content="'.url('/images/1920x1080/photo_playing_1920x1080.jpg').'">');
});

test('the shared image exists and is large enough to preview', function () {
    $path = public_path('images/1920x1080/photo_playing_1920x1080.jpg');

    expect(file_exists($path))->toBeTrue();

    [$width, $height] = getimagesize($path);

    // Facebook drops anything under 200x200 and crops towards 1.91:1.
    expect($width)->toBe(1920)
        ->and($height)->toBe(1080);
});
