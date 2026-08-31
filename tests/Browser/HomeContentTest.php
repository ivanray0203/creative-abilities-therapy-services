<?php

use App\Models\Contact;

/**
 * The home page and the shared footer are static React markup, so the copy
 * is only observable once rendered. These cover the marketing sections a
 * visitor reads top-to-bottom, plus the footer contact details that are the
 * easiest thing to leave stale.
 */
it('leads with the mission statement and a single intake call to action', function () {
    visit('/')
        ->assertSee('Welcome to Creative Abilities Therapy Services')
        ->assertSee('Empowering Every')
        ->assertSee('Embracing Every')
        ->assertSee(
            'We provide individualized, family-centred support that helps '.
            'children build confidence, develop meaningful skills, and '.
            'participate more fully in everyday life.',
        )
        ->assertSee('Start Intake')
        ->assertNoJavaScriptErrors();
});

it('describes the therapy and support services on offer', function () {
    visit('/')
        ->assertSee('What We Offer')
        ->assertSee('Our Therapy & Support Services')
        ->assertSee(
            'Our multidisciplinary team works closely with families to '.
            'create support plans that reflect each child’s unique strengths, '.
            'needs, and goals.',
        )
        ->assertSee('Explore Our Services')
        ->assertNoJavaScriptErrors();
});

it('lists every funded service available through FSCD', function () {
    $page = visit('/');

    $page->assertSee('FSCD Partnership')
        ->assertSee('Approved FSCD Service Provider')
        ->assertSee('What We Offer Through FSCD')
        ->assertSee('Learn More About FSCD');

    foreach ([
        'Specialized Services (SS)',
        'Behavioural and Developmental Support (BDS)',
        'Counselling Services',
        'Respite Services',
        'Direct Billing at FSCD-Approved Rates',
    ] as $service) {
        $page->assertSee($service);
    }

    $page->assertNoJavaScriptErrors();
});

it('sets out who we are and how we approach care', function () {
    $page = visit('/');

    $page->assertSee('Who We Are')
        ->assertSee(
            'committed to helping children grow, participate, and thrive '.
            'through individualized, evidence-based care',
        )
        ->assertSee('Our Approach');

    foreach ([
        'Evidence-Based & Individualized Care',
        'Family-Centred & Collaborative Support',
        'Flexible Service Delivery',
        'Compassionate & Dedicated Professionals',
    ] as $pillar) {
        $page->assertSee($pillar);
    }

    $page->assertNoJavaScriptErrors();
});

it('names the communities served and how services are delivered', function () {
    $page = visit('/');

    $page->assertSee('Where We Serve')
        ->assertSee('Service Locations')
        ->assertSee('Our Primary Service Area')
        ->assertSee('Calgary and surrounding communities')
        ->assertSee('Communities We Serve')
        ->assertSee(
            'Services are available in the home, in the community, or '.
            'through secure online sessions',
        );

    foreach ([
        'Airdrie',
        'Chestermere',
        'Cochrane',
        'Okotoks',
        'Strathmore',
        'Nearby Communities',
    ] as $community) {
        $page->assertSee($community);
    }

    $page->assertNoJavaScriptErrors();
});

it('closes with the intake invitation and current contact details', function () {
    visit('/')
        ->assertSee('Start Your Journey with CATS')
        ->assertSee('Ready to Get Started?')
        ->assertSee('connect with you within 1–2 business days')
        ->assertSee('Have a Question?')
        ->assertSee('(587) 436-9825')
        ->assertSee('info@creativeabilitiestherapyservices.ca')
        ->assertSee('Monday–Friday: 8:00 AM - 6:00 PM')
        ->assertSee('Saturday: By Appointment')
        ->assertSee('Community & Respite Service')
        ->assertNoJavaScriptErrors();
});

it('records the subject the visitor typed rather than a canned one', function () {
    visit('/')
        ->fill('#contact-name', 'Rosa Delgado')
        ->fill('#contact-email', 'rosa@example.com')
        ->fill('#contact-phone', '5874369825')
        ->fill('#contact-subject', 'Speech therapy waitlist')
        ->fill('#contact-message', 'Is there a waitlist for speech therapy?')
        ->click('#contact-submit')
        ->assertSee('We will contact you as soon as possible.')
        ->assertNoJavaScriptErrors();

    expect(Contact::first()->contact)->toMatchArray([
        'name' => 'Rosa Delgado',
        'subject' => 'Speech therapy waitlist',
    ]);
});

it('rejects a contact submission with no subject', function () {
    visit('/')
        ->fill('#contact-name', 'Rosa Delgado')
        ->fill('#contact-email', 'rosa@example.com')
        ->fill('#contact-message', 'Is there a waitlist for speech therapy?')
        ->click('#contact-submit')
        ->assertSee('The subject field is required.')
        ->assertNoJavaScriptErrors();

    expect(Contact::count())->toBe(0);
});
