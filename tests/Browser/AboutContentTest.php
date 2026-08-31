<?php

/**
 * The About page mixes copy written inline with copy that lives in
 * `resources/js/lib/content/about.ts`, so the rendered page is the only place
 * both halves can be checked together.
 */
it('opens with the mission framing and where services are delivered', function () {
    visit('/about')
        ->assertSee('Welcome to Creative Abilities Therapy Services')
        ->assertSee('Embracing Every')
        ->assertSee(
            'Creative Abilities Therapy Services provides individualized, '.
            'evidence-based therapy and developmental support for children '.
            'and families.',
        )
        ->assertSee(
            'Services may be provided at home, in the community, and in '.
            'other appropriate settings throughout Calgary and surrounding '.
            'communities.',
        )
        ->assertNoJavaScriptErrors();
});

it('states the mission', function () {
    visit('/about')
        ->assertSee('Our Mission')
        ->assertSee('Empowering Every Child, Embracing Every Ability.')
        ->assertSee(
            'Our mission is to empower every child by recognizing their '.
            'unique strengths, abilities, and needs.',
        )
        ->assertSee(
            'communication, sensory processing, emotional regulation, '.
            'behaviour, physical development, and independence',
        )
        ->assertNoJavaScriptErrors();
});

it('lays out the philosophy and the everyday-life skills it builds', function () {
    $page = visit('/about');

    $page->assertSee('Our Philosophy')
        ->assertSee('Helping Children Play, Grow, and Thrive')
        ->assertSee(
            'We believe children learn and grow best when they feel '.
            'supported, understood, and encouraged.',
        )
        ->assertSee('Meeting Children Where They Are')
        ->assertSee('Collaborative Approach')
        ->assertSee('Nurturing Environments')
        ->assertSee('Building Skills for Everyday Life');

    foreach ([
        'Confidence',
        'Independence',
        'Everyday Skills',
        'Meaningful Participation',
    ] as $skill) {
        $page->assertSee($skill);
    }

    $page->assertNoJavaScriptErrors();
});

it('names the disciplines on the team and links to the team page', function () {
    visit('/about')
        ->assertSee('Our Team')
        ->assertSee('A Collaborative Multidisciplinary Team')
        ->assertSee(
            'Our team includes Speech-Language Pathologists, Psychologists, '.
            'Occupational Therapists, Physiotherapists, Behavioural '.
            'Consultants, Behavioural & Developmental Aides, and Community '.
            'and Respite Aides',
        )
        ->click('Meet Our Team')
        ->assertPathIs('/team')
        ->assertNoJavaScriptErrors();
});

it('sets out all ten facets of the approach', function () {
    $page = visit('/about');

    $page->assertSee('Our Approach')
        ->assertSee('How We Support Children and Families')
        ->assertSee(
            'we recognize that families play an important role in a child’s '.
            'growth and development',
        );

    foreach ([
        'Child-Centred Care',
        'Collaborative Partnerships',
        'Evidence-Based Methods',
        'Functional Goals',
        'Holistic Development',
        'Play-Based Learning',
        'Individualized Behavioural Support',
        'Family Involvement and Education',
        'School and Community Collaboration',
        'Comprehensive Assessments and Evaluations',
    ] as $facet) {
        $page->assertSee($facet);
    }

    $page->assertNoJavaScriptErrors();
});

it('describes the behavioural and developmental aides, not facilitators', function () {
    visit('/about')
        ->assertSee('Behavioural & Developmental Aides')
        ->assertSee('Creating Supportive Spaces for Growth')
        ->assertSee(
            'provide individualized support that helps children build skills '.
            'through play, social interaction, everyday routines, and '.
            'meaningful activities',
        )
        ->assertSee(
            'We work alongside parents and caregivers to support progress, '.
            'celebrate meaningful milestones',
        )
        ->assertDontSee('Child Development Facilitators')
        ->assertNoJavaScriptErrors();
});

it('closes with the invitation to work together', function () {
    visit('/about')
        ->assertSee('Start Your Journey with CATS')
        ->assertSee("Let's Work Together")
        ->assertSee(
            'Connect with our team to learn more about our services and how '.
            'we can support your child\'s strengths, needs, goals, and '.
            'everyday participation.',
        )
        ->assertSee('Start Intake')
        ->assertSee('Contact Us')
        ->assertNoJavaScriptErrors();
});

it('no longer carries the founder profile, which moved to the team pages', function () {
    visit('/about')
        ->assertDontSee('Meet Our Founder')
        ->assertDontSee('Mary Ann Lerit')
        ->assertDontSee('Contact Mary Ann')
        ->assertNoJavaScriptErrors();
});
