<?php

use App\Models\Service;

/**
 * The services index and the seven detail pages are static: their copy lives
 * in resources/js/lib/content/service-list.ts and no database row backs them.
 * The detail page renders any service from one template, so the per-service
 * tests check that each one's own copy reaches the page rather than
 * re-checking the template's structure.
 */
it('introduces the services index', function () {
    visit('/services')
        ->assertSee('Our Services')
        ->assertSee('Comprehensive Support for Children & Families')
        ->assertSee(
            'We provide individualized, evidence-based therapy and '.
            'developmental support for children and families.',
        )
        ->assertSee(
            'services available in home and community settings throughout '.
            'Calgary and surrounding communities',
        )
        ->assertNoJavaScriptErrors();
});

it('lists all seven services with their taglines', function () {
    $page = visit('/services');

    foreach ([
        ['Speech-Language Therapy', 'Supporting Communication, Connection & Participation'],
        ['Psychology Services & Counselling', 'Supporting Emotional Well-Being'],
        ['Occupational Therapy', 'Supporting Everyday Skills'],
        ['Physiotherapy', 'Supporting Movement, Mobility & Physical Development'],
        ['Behavioural Therapy & Consulting', 'Understanding Behaviour, Building Skills'],
        ['Behavioural & Developmental Aide Services', 'Supporting Skills, Confidence & Independence'],
        ['Community & Respite Aide Services', 'Supporting Participation, Independence & Meaningful Experiences'],
    ] as [$name, $tagline]) {
        $page->assertSee($name)->assertSee($tagline);
    }

    $page->assertNoJavaScriptErrors();
});

it('orders the service cards as listed', function () {
    $codes = visit('/services')->script(
        "JSON.stringify(Array.from(document.querySelectorAll('#services [id]')).map((el) => el.id))",
    );

    expect(json_decode($codes, true))->toBe([
        'SLTS-202',
        'BCCPS-303',
        'OTS-101',
        'P-303',
        'BTC-505',
        'BDAS-303',
        'CRAS-404',
    ]);
});

it('walks through the five-step journey', function () {
    $page = visit('/services');

    $page->assertSee('Your Journey with CATS')
        ->assertSee(
            'From your first contact with Creative Abilities Therapy Services '.
            'to ongoing progress',
        );

    foreach ([
        'Intake',
        'Observation & Informal Assessment',
        'Service Planning',
        'Begin Services',
        'Review Progress',
    ] as $step) {
        $page->assertSee($step);
    }

    $page->assertSee('Service Providers Program Plan (SPPP)')
        ->assertSee('Individualized Service Plan (ISP)')
        ->assertNoJavaScriptErrors();
});

it('names the four regulatory colleges', function () {
    visit('/services')
        ->assertSee('Professional Standards & Regulation')
        ->assertSee('Alberta College of Speech-Language Pathologists and Audiologists')
        ->assertSee('College of Alberta Psychologists')
        ->assertSee('Alberta College of Occupational Therapists')
        ->assertSee('College of Physiotherapists of Alberta')
        ->assertNoJavaScriptErrors();
});

it('sets out funding options and why families choose CATS', function () {
    visit('/services')
        ->assertSee('Funding & Payment Options')
        ->assertSee('Creative Abilities Therapy Services is an approved FSCD service provider')
        ->assertSee('Some extended health benefit plans may cover eligible therapy services.')
        ->assertSee('Why Families Choose Creative Abilities Therapy Services?')
        ->assertSee('Home & Community Services')
        ->assertSee('Inclusive & Respectful Care')
        ->assertNoJavaScriptErrors();
});

it('opens a service detail page from its card', function () {
    visit('/services')
        ->click('Learn More')
        ->assertPathIs('/servicesDetails/SLTS-202')
        ->assertSee('Speech-Language Therapy')
        ->assertNoJavaScriptErrors();
});

it('renders every shared section on a detail page', function () {
    $page = visit('/servicesDetails/OTS-101');

    foreach ([
        'How Occupational Therapy Can Help',
        'What to Expect',
        'Family-Centred Occupational Therapy',
        'Who May Benefit?',
        'Our Approach',
        'Where Occupational Therapy May Be Provided',
        'Collaboration with Your Child’s Support Team',
        'Funding & Payment Options',
        'Ready to Get Started?',
    ] as $heading) {
        $page->assertSee($heading);
    }

    $page->assertSee('Start Intake')
        ->assertSee('Contact Us')
        ->assertNoJavaScriptErrors();
});

it('carries each service’s own copy onto its detail page', function (string $code, string $marker) {
    visit("/servicesDetails/{$code}")->assertSee($marker)->assertNoJavaScriptErrors();
})->with([
    ['SLTS-202', 'Augmentative & Alternative Communication (AAC)'],
    ['BCCPS-303', 'Helping children develop strategies to understand and manage worries, fears, stress, and anxious feelings.'],
    ['OTS-101', 'Alberta College of Occupational Therapists (ACOT)'],
    ['P-303', 'College of Physiotherapists of Alberta (CPTA)'],
    ['BTC-505', 'Behavioural & Developmental Aide Collaboration'],
    ['BDAS-303', 'Behavioural & Developmental Aides and Behavioural Consultants'],
    ['CRAS-404', 'Respite Aide Services'],
]);

it('points an unknown service back at the services index', function () {
    visit('/servicesDetails/NOPE-000')
        ->assertSee('We couldn’t find that service')
        ->click('View All Services')
        ->assertPathIs('/services')
        ->assertNoJavaScriptErrors();
});

it('renders the services index without touching the database', function () {
    // Proves the page is genuinely static: with the services table emptied it
    // still lists everything.
    Service::query()->delete();

    visit('/services')
        ->assertSee('Speech-Language Therapy')
        ->assertSee('Community & Respite Aide Services')
        ->assertNoJavaScriptErrors();
});

it('renders a detail page without touching the database', function () {
    Service::query()->delete();

    visit('/servicesDetails/OTS-101')
        ->assertSee('Occupational Therapy')
        ->assertSee('How Occupational Therapy Can Help')
        ->assertNoJavaScriptErrors();
});
