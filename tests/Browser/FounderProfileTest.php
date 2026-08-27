<?php

/**
 * `/team` is the leadership page: the founder's profile followed by the
 * remaining leaders. `/team/founder` reuses the same founder card, so both
 * routes are covered here.
 */
it('introduces the leadership page', function () {
    visit('/team')
        ->assertSee('Meet Our Leaders')
        ->assertSee('Dedicated to Supporting Children & Families')
        ->assertSee(
            'Our Founder & Director brings extensive experience in pediatric '.
            'Occupational Therapy, with specialized training in sensory '.
            'processing and feeding.',
        )
        ->assertNoJavaScriptErrors();
});

it('shows the founder profile on both team routes', function (string $path) {
    visit($path)
        ->assertSee('Mary Ann Lerit, B.Sc. OT, OT Reg. (AB)')
        ->assertSee('Founder & Director | Registered Occupational Therapist')
        ->assertSee(
            'Mary Ann (Ann) holds a Bachelor of Science in Occupational '.
            'Therapy from St. Jude College in Manila, Philippines.',
        )
        ->assertSee(
            'Her pediatric practice is supported by specialized training in '.
            'sensory processing and Sequential Oral Sensory (SOS) Feeding',
        )
        ->assertSee(
            'Outside of her professional work, Mary Ann enjoys travelling '.
            'across North America',
        )
        ->assertSee('Credentials & Certifications')
        ->assertSee(
            'Registered Occupational Therapist — Alberta College of '.
            'Occupational Therapists, since 2016',
        )
        ->assertSee('Specialized Training — Sequential Oral Sensory (SOS) Feeding')
        ->assertSee('Contact Mary Ann')
        ->assertNoJavaScriptErrors();
})->with([
    '/team',
    '/team/founder',
]);

it('profiles the operations and finance lead', function () {
    $page = visit('/team');

    $page->assertSee('Bernard Lerit')
        ->assertSee('Operations & Chief Financial Officer')
        ->assertSee('Supporting the Operations Behind Quality Care')
        ->assertSee(
            'He plays an important role in supporting the organization’s '.
            'day-to-day operations, financial administration, and internal '.
            'systems',
        )
        ->assertSee('Commitment to Creative Abilities')
        ->assertSee('Contact Bernard');

    foreach ([
        'Operations Management',
        'Financial Oversight',
        'Administrative Systems',
        'Organizational Planning',
        'Team Support',
    ] as $role) {
        $page->assertSee($role);
    }

    $page->assertNoJavaScriptErrors();
});

it('profiles the behavioural and respite programs lead', function () {
    $page = visit('/team');

    $page->assertSee('Bryan Lerit')
        ->assertSee(
            'Operations & Program Lead, Behavioural & Respite Support Services',
        )
        ->assertSee('Connecting Families, Support Teams & Programs')
        ->assertSee(
            'Bryan oversees and coordinates Behavioural & Developmental '.
            'Aides, Community Aides, and Respite Aides.',
        )
        ->assertSee('Commitment to Children & Families')
        ->assertSee('Contact Bryan');

    foreach ([
        'Behavioural & Developmental Aide Services',
        'Community & Respite Support Services',
        'Program Operations',
        'Contractor Onboarding & Coordination',
        'Family & Team Communication',
        'Service Delivery Support',
    ] as $role) {
        $page->assertSee($role);
    }

    $page->assertNoJavaScriptErrors();
});

it('orders the leaders founder-first', function () {
    $inOrder = visit('/team')->script(
        "(() => {
            const ids = Array.from(document.querySelectorAll('#founder, #leader-1, #leader-2'))
                .map((el) => el.id);
            return JSON.stringify(ids);
        })()",
    );

    expect($inOrder)->toBe('["founder","leader-1","leader-2"]');
});

it('gives every contact button somewhere to land', function () {
    $page = visit('/team')->assertSee('Have a Question?');

    foreach (['Contact Mary Ann', 'Contact Bernard', 'Contact Bryan'] as $button) {
        $page->click($button)->assertSee('Send Message');
    }

    $page->assertNoJavaScriptErrors();
});

it('keeps the values section on the founder page', function () {
    visit('/team/founder')
        ->assertSee('Our Values')
        ->assertSee('What Drives Our Team')
        ->assertNoJavaScriptErrors();
});

it('reaches the team page from the footer', function () {
    visit('/')
        ->click('Our Team')
        ->assertPathIs('/team')
        ->assertSee('Meet Our Leaders')
        ->assertNoJavaScriptErrors();
});

it('alternates the portraits: founder left, then right, then left', function () {
    $sides = visit('/team')->script(
        "JSON.stringify(['#founder', '#leader-1', '#leader-2'].map((sel) => {
            const img = document.querySelector(sel + ' img');
            const row = img.closest('.grid, .flex-col');
            return img.getBoundingClientRect().left <
                row.getBoundingClientRect().left + row.getBoundingClientRect().width / 2
                ? 'left'
                : 'right';
        }))",
    );

    expect(json_decode($sides, true))->toBe(['left', 'right', 'left']);
});

it('shows a portrait for every leader', function () {
    $sources = visit('/team')->script(
        "JSON.stringify(Array.from(document.querySelectorAll('#leader-1 img, #leader-2 img')).map((i) => i.getAttribute('src')))",
    );

    expect(json_decode($sources, true))->toBe([
        '/images/leader-placeholder-bernard.svg',
        '/images/leader-placeholder-bryan.svg',
    ]);

    foreach (json_decode($sources, true) as $source) {
        expect(public_path(ltrim($source, '/')))->toBeFile();
    }
});
