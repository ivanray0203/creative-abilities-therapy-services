<?php

/**
 * The FSCD page is static content: inline copy plus the process steps,
 * BDS/SS comparison, and provider reasons in
 * resources/js/lib/content/fscd.ts.
 */
it('opens with what CATS provides under FSCD', function () {
    visit('/fscd')
        ->assertSee('FSCD Partnership')
        ->assertSee('Family Support for Children with Disabilities (FSCD)')
        ->assertSee(
            'is an approved Family Support for Children with Disabilities '.
            '(FSCD) service provider.',
        )
        ->assertSee(
            'helping children access individualized services based on their '.
            'developmental, behavioural, physical, communication, and '.
            'emotional needs',
        )
        ->assertNoJavaScriptErrors();
});

it('explains how CATS supports a family through services', function () {
    visit('/fscd')
        ->assertSee('Supporting Your Family Through Services')
        ->assertSee('Empowering Every Child, Embracing Every Ability')
        ->assertSee(
            'We collaborate with families, FSCD caseworkers, and service '.
            'providers to develop and deliver individualized support',
        )
        ->assertSee(
            'Our goal is to provide coordinated, family-centred support that '.
            'helps children build meaningful skills',
        )
        ->assertSee('Start Intake')
        ->assertSee('Contact Us')
        ->assertNoJavaScriptErrors();
});

it('describes the FSCD program and where to check eligibility', function () {
    visit('/fscd')
        ->assertSee('What is FSCD?')
        ->assertSee('Understanding the FSCD Program')
        ->assertSee(
            'offered by the Government of Alberta to provide funding and '.
            'support to eligible families of children with disabilities',
        )
        ->assertSee(
            'funding may help support services such as therapy, behavioural '.
            'and developmental support, respite, equipment',
        )
        ->assertSee('FSCD Eligibility')
        ->assertSee('Families must apply directly through FSCD')
        ->assertSee('Learn More About FSCD Eligibility')
        ->assertNoJavaScriptErrors();
});

it('sends the eligibility link to the Alberta FSCD program', function () {
    $link = visit('/fscd')->script(
        "(() => {
            const anchor = Array.from(document.querySelectorAll('a')).find(
                (a) => a.textContent.includes('Learn More About FSCD Eligibility'),
            );
            return JSON.stringify({ href: anchor.href, target: anchor.target, rel: anchor.rel });
        })()",
    );

    expect(json_decode($link, true))->toBe([
        'href' => 'https://www.alberta.ca/fscd-eligibility',
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
    ]);
});

it('sets out both FSCD-approved service models', function () {
    visit('/fscd')
        ->assertSee('FSCD-Approved Services')
        ->assertSee('Behavioural and Developmental Support (BDS)')
        ->assertSee(
            'BDS is generally a less intensive service model and is typically '.
            'provided for approximately six months.',
        )
        ->assertSee('Specialized Services (SS)')
        ->assertSee(
            'SS is generally a more intensive, multidisciplinary service '.
            'model and is typically provided over a 12-month period.',
        )
        ->assertSee(
            'Some families may transition from Behavioural and Developmental '.
            'Support (BDS) to Specialized Services (SS)',
        )
        ->assertNoJavaScriptErrors();
});

it('compares BDS and specialized services row by row', function () {
    $page = visit('/fscd')->assertSee(
        'Key Differences Between BDS and Specialized Services',
    );

    foreach ([
        'Service Length',
        'Level of Support',
        'Clinical Team',
        'Aide Support',
        'Clinical Coordination',
        'Service Planning',
        'Focus',
    ] as $row) {
        $page->assertSee($row);
    }

    $page->assertSee('Typically 6 months')
        ->assertSee('Typically 12 months')
        ->assertSee('Up to 2 clinicians')
        ->assertSee('Up to 4 clinicians')
        ->assertSee('Clinical Coordinator')
        ->assertNoJavaScriptErrors();
});

it('keeps the comparison table scrollable rather than widening the page', function () {
    $overflow = visit('/fscd')->script(
        "(() => {
            const table = document.querySelector('table');
            const scroller = table.parentElement;
            return JSON.stringify({
                overflowX: getComputedStyle(scroller).overflowX,
                bodyFits: document.body.scrollWidth <= window.innerWidth + 1,
            });
        })()",
    );

    expect(json_decode($overflow, true))->toBe([
        'overflowX' => 'auto',
        'bodyFits' => true,
    ]);
});

it('walks through the five process steps', function () {
    $page = visit('/fscd')
        ->assertSee('How FSCD Services Work with Creative Abilities Therapy Services')
        ->assertSee(
            'Once your child has been approved for FSCD services, Creative '.
            'Abilities Therapy Services works collaboratively with your '.
            'family, FSCD caseworker, and service team',
        );

    foreach ([
        'Step 1 — FSCD Approval',
        'Step 2 — Choose Creative Abilities Therapy Services',
        'Step 3 — Planning & Goal Development',
        'Step 4 — Build Your Service Team & Begin Services',
        'Step 5 — Progress Review & Ongoing Collaboration',
    ] as $step) {
        $page->assertSee($step);
    }

    $page->assertSee('Service Providers Program Plan (SPPP)')
        ->assertSee('Individualized Service Plan (ISP)')
        ->assertSee('Renew BDS and continue with another service period')
        ->assertSee('Transition to Specialized Services (SS)')
        ->assertSee('Adjust services or goals based on the child’s progress')
        ->assertSee(
            'Through ongoing collaboration, we work to create consistency '.
            'across home and community settings',
        )
        ->assertNoJavaScriptErrors();
});

it('lists all eight reasons families choose CATS', function () {
    $page = visit('/fscd')
        ->assertSee('Why Families Choose CATS for FSCD Services')
        ->assertSee(
            'we provide more than individual services',
        );

    foreach ([
        'Direct Billing & FSCD-Approved Rates',
        'FSCD Expertise',
        'Multidisciplinary Team Approach',
        'Individualized Support Plans',
        'Family-Centred Care',
        'Inclusive & Respectful Care',
        'Clear & Ongoing Communication',
        'School & Community Collaboration',
    ] as $reason) {
        $page->assertSee($reason);
    }

    $page->assertSee('Our Commitment to Excellence')
        ->assertSee(
            'We are committed to providing coordinated, evidence-based, and '.
            'family-centred support',
        )
        ->assertNoJavaScriptErrors();
});

it('closes with the invitation to access FSCD services', function () {
    visit('/fscd')
        ->assertSee('Start Your Journey with CATS')
        ->assertSee('Ready to Access FSCD Services?')
        ->assertSee(
            'If your family has been approved for FSCD services and you are '.
            'looking for a service provider',
        )
        ->assertSee('Have a Question?')
        ->assertNoJavaScriptErrors();
});

it('gives the contact buttons somewhere to land', function () {
    visit('/fscd')
        ->click('Contact Us')
        ->assertSee('Send Message')
        ->assertNoJavaScriptErrors();
});
