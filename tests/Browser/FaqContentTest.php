<?php

/**
 * The FAQ is static content from resources/js/lib/content/faq.ts, rendered
 * into a searchable, category-filtered accordion. Answers only appear once
 * their accordion item is opened, so tests click a question before reading it.
 */
it('introduces the FAQ', function () {
    visit('/faq')
        ->assertSee('Questions & Answers')
        ->assertSee('Frequently Asked Questions')
        ->assertSee(
            'Find answers to common questions about our services, intake '.
            'process, FSCD, billing, privacy, and more.',
        )
        ->assertSee('If you don’t see the information you’re looking for')
        ->assertNoJavaScriptErrors();
});

it('groups the questions into four categories', function () {
    $page = visit('/faq');

    foreach ([
        'Services & Billing',
        'FSCD',
        'Getting Started',
        'Privacy & Safety',
    ] as $category) {
        $page->assertSee($category);
    }

    $page->assertNoJavaScriptErrors();
});

it('lists every services and billing question', function () {
    $page = visit('/faq');

    foreach ([
        'What services do you offer?',
        'Do you offer direct billing?',
        'What are your rates?',
        'What age groups do you work with?',
        'What if I need to cancel or reschedule an appointment?',
        'Where can services be provided?',
        'How do you track my child’s progress?',
        'How often will we have therapy sessions?',
    ] as $question) {
        $page->assertSee($question);
    }

    $page->assertNoJavaScriptErrors();
});

it('lists the remaining questions', function () {
    $page = visit('/faq');

    foreach ([
        'What is FSCD and do I qualify?',
        'How do I apply for FSCD funding?',
        'How soon can we begin services?',
        'What happens during the intake process?',
        'What qualifications do your therapists have?',
        'How do you protect my family’s privacy?',
        'Do you share information with schools or doctors?',
    ] as $question) {
        $page->assertSee($question);
    }

    $page->assertNoJavaScriptErrors();
});

it('names all seven services in the services answer', function () {
    visit('/faq')
        ->click('What services do you offer?')
        ->assertSee(
            'Speech-Language Therapy, Psychology Services & Counselling, '.
            'Occupational Therapy, Physiotherapy, Behavioural Therapy & '.
            'Consulting, Behavioural & Developmental Aide Services, and '.
            'Community & Respite Aide Services',
        )
        ->assertNoJavaScriptErrors();
});

it('gives the current age range', function () {
    visit('/faq')
        ->click('What age groups do you work with?')
        ->assertSee('primarily supports children and youth ages 3–17')
        ->assertNoJavaScriptErrors();
});

it('explains the cancellation window', function () {
    visit('/faq')
        ->click('What if I need to cancel or reschedule an appointment?')
        ->assertSee('please provide at least 24 hours’ notice whenever possible')
        ->assertSee('If the session can be rescheduled, no cancellation fee will apply.')
        ->assertNoJavaScriptErrors();
});

it('breaks session frequency down by service model', function () {
    visit('/faq')
        ->click('How often will we have therapy sessions?')
        ->assertSee('Behavioural and Developmental Support (BDS):')
        ->assertSee('typically 1–2 clinician sessions per month')
        ->assertSee('Specialized Services (SS):')
        ->assertSee('typically 2–4 clinician sessions per month')
        ->assertSee('Private Services:')
        ->assertNoJavaScriptErrors();
});

it('is clear that CATS does not decide FSCD eligibility', function () {
    visit('/faq')
        ->click('What is FSCD and do I qualify?')
        ->assertSee(
            'Creative Abilities Therapy Services does not determine FSCD '.
            'eligibility. Families must apply directly through FSCD.',
        )
        ->assertNoJavaScriptErrors();
});

it('links the FSCD funding answer to the Alberta eligibility page', function () {
    $link = visit('/faq')
        ->click('How do I apply for FSCD funding?')
        ->script(
            "(() => {
                const anchor = Array.from(document.querySelectorAll('a')).find(
                    (a) => a.textContent.includes('Learn More About FSCD Eligibility'),
                );
                return JSON.stringify({ href: anchor.href, target: anchor.target });
            })()",
        );

    expect(json_decode($link, true))->toBe([
        'href' => 'https://www.alberta.ca/fscd-eligibility',
        'target' => '_blank',
    ]);
});

it('names each regulatory college in the qualifications answer', function () {
    $page = visit('/faq')->click('What qualifications do your therapists have?');

    foreach ([
        'Alberta College of Occupational Therapists (ACOT)',
        'Alberta College of Speech-Language Pathologists and Audiologists (ACSLPA)',
        'College of Physiotherapists of Alberta (CPTA)',
        'College of Alberta Psychologists (CAP)',
    ] as $college) {
        $page->assertSee($college);
    }

    $page->assertSee(
        'Our team also includes Behavioural Consultants and Behavioural & '.
        'Developmental Aides',
    )->assertNoJavaScriptErrors();
});

it('states the privacy and information-sharing position', function () {
    visit('/faq')
        ->click('Do you share information with schools or doctors?')
        ->assertSee(
            'We only share information with schools, physicians, childcare '.
            'providers, or other professionals when appropriate and with the '.
            'family’s consent',
        )
        ->assertNoJavaScriptErrors();
});

it('filters the list by category', function () {
    visit('/faq')
        ->click('Privacy & Safety')
        ->assertSee('How do you protect my family’s privacy?')
        ->assertDontSee('What are your rates?')
        ->assertNoJavaScriptErrors();
});

it('searches across questions and answers', function () {
    visit('/faq')
        ->fill('input[aria-label="Search questions"]', 'cancellation')
        ->assertSee('What if I need to cancel or reschedule an appointment?')
        ->assertDontSee('What services do you offer?')
        ->assertNoJavaScriptErrors();
});

it('closes with the contact form', function () {
    visit('/faq')
        ->assertSee('Have a Question?')
        ->assertSee(
            'Not sure where to start or which service may be right for your '.
            'family?',
        )
        ->assertSee('Send Message')
        ->assertNoJavaScriptErrors();
});
