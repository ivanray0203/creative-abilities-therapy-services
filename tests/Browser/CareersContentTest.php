<?php

use App\Models\Career;
use Database\Seeders\CareerSeeder;
use Illuminate\Support\Facades\DB;

/**
 * The careers index and the postings behind it are database-backed, so every
 * test seeds the real postings first. Shared copy (the application process and
 * timeline) lives in resources/js/lib/content/careers-config.ts; everything
 * position-specific comes from the `careers` table.
 */
beforeEach(function () {
    $this->seed(CareerSeeder::class);
});

it('opens with the invitation to build a career at CATS', function () {
    visit('/careers')
        ->assertSee('Join Our Team')
        ->assertSee('Build Your Career with Creative Abilities Therapy Services')
        ->assertSee(
            'We’re looking for dedicated professionals who are passionate '.
            'about supporting children and families',
        )
        ->assertSee(
            'Speech-Language Pathologists, Psychologists, Occupational '.
            'Therapists, Physiotherapists, Behavioural Consultants, '.
            'Behavioural & Developmental Aides, and Community & Respite Aides',
        )
        ->assertNoJavaScriptErrors();
});

it('walks through the four application steps and the timeline', function () {
    $page = visit('/careers')
        ->assertSee('Application Process')
        ->assertSee('Your Journey to Joining CATS')
        ->assertSee(
            'Our application process is designed to be clear and '.
            'straightforward',
        );

    foreach (['Apply', 'Review', 'Interview', 'Offer & Onboarding'] as $step) {
        $page->assertSee($step);
    }

    $page->assertSee('Typical Timeline: Approximately 1–2 Weeks')
        ->assertSee(
            'Timelines may vary depending on the position, interview '.
            'availability, reference checks',
        )
        ->assertNoJavaScriptErrors();
});

it('lists all nine reasons to work with CATS', function () {
    $page = visit('/careers')
        ->assertSee('Why Work With CATS')
        ->assertSee(
            'We offer flexible contractor opportunities designed to support '.
            'professional growth, collaboration, and work-life balance.',
        );

    foreach ([
        'Competitive Contract Rates',
        'Flexible Scheduling',
        'Community-Based Opportunities',
        'Learning & Growth Opportunities',
        'Training & Resources',
        'Collaborative Team Environment',
        'Team Connection',
        'Administrative Support',
        'Resources & Tools',
    ] as $reason) {
        $page->assertSee($reason);
    }

    $page->assertSee(
        'Whether you’re an experienced professional or just beginning your '.
        'career, we’d be happy to hear from you.',
    )->assertNoJavaScriptErrors();
});

it('lists every open position with its rate and hours', function () {
    $page = visit('/careers')
        ->assertSee('Open Positions')
        ->assertSee('Current Openings')
        ->assertSee(
            'Explore our current contract opportunities and find a role that '.
            'aligns with your experience',
        );

    foreach ([
        ['Speech-Language Pathologist (SLP)', 'Starting at $65.44/hour'],
        ['Psychologist', 'Starting at $63.94/hour'],
        ['Occupational Therapist (OT)', 'Starting at $61.59/hour'],
        ['Physiotherapist (PT)', 'Starting at $53.73/hour'],
        ['Behavioural Consultant/Therapist (BC)', 'Starting at $50.21/hour'],
        ['Behavioural & Developmental Aide', 'Starting at $20.28/hour'],
        ['Community & Respite Aide', 'Starting at $15.01/hour'],
    ] as [$position, $rate]) {
        $page->assertSee($position)->assertSee($rate);
    }

    $page->assertSee('Calgary & Surrounding Areas')
        ->assertSee('Contract Position')
        ->assertSee('Approximately 15–30 hours/week, based on caseload, referrals, and availability')
        ->assertNoJavaScriptErrors();
});

it('orders the postings as listed', function () {
    expect(
        Career::query()->where('is_active', true)->orderBy('sort_order')->pluck('position')->all(),
    )->toBe([
        'Speech-Language Pathologist (SLP)',
        'Psychologist',
        'Occupational Therapist (OT)',
        'Physiotherapist (PT)',
        'Behavioural Consultant/Therapist (BC)',
        'Behavioural & Developmental Aide',
        'Community & Respite Aide',
    ]);
});

it('opens a posting from its View Details button', function () {
    $slp = Career::query()->where('position', 'Speech-Language Pathologist (SLP)')->firstOrFail();

    visit('/careers')
        ->click("#career-details-{$slp->id}")
        ->assertPathIs("/careers/{$slp->id}")
        ->assertSee('Speech-Language Pathologist (SLP)')
        ->assertSee('Contract Opportunity')
        ->assertNoJavaScriptErrors();
});

it('renders every shared section on a posting', function () {
    $ot = Career::query()->where('position', 'Occupational Therapist (OT)')->firstOrFail();

    $page = visit("/careers/{$ot->id}");

    foreach ([
        'Join Creative Abilities Therapy Services',
        'About the Role',
        'Key Responsibilities',
        'Multidisciplinary Collaboration',
        'Qualifications',
        'What We Offer',
        'FSCD Services',
        'Independent Contractor Opportunity',
        'Application Process',
        'Ready to Join Our Team?',
    ] as $heading) {
        $page->assertSee($heading);
    }

    $page->assertSee('Location:')
        ->assertSee('Position Type:')
        ->assertSee('Starting Rate:')
        ->assertSee('Hours:')
        ->assertSee('Apply Now')
        ->assertSee('View All Open Positions')
        ->assertNoJavaScriptErrors();
});

it('carries each posting’s own copy onto its page', function (string $position, string $marker) {
    $career = Career::query()->where('position', $position)->firstOrFail();

    visit("/careers/{$career->id}")->assertSee($marker)->assertNoJavaScriptErrors();
})->with([
    ['Speech-Language Pathologist (SLP)', 'Augmentative and Alternative Communication (AAC)'],
    ['Psychologist', 'College of Alberta Psychologists (CAP)'],
    ['Occupational Therapist (OT)', 'Alberta College of Occupational Therapists (ACOT)'],
    ['Physiotherapist (PT)', 'College of Physiotherapists of Alberta (CPTA)'],
    ['Behavioural Consultant/Therapist (BC)', 'Behavioural & Developmental Aide Collaboration'],
    ['Behavioural & Developmental Aide', 'Documentation & Team Communication'],
]);

it('omits the FSCD section from the posting that has none', function () {
    $slp = Career::query()->where('position', 'Speech-Language Pathologist (SLP)')->firstOrFail();

    visit("/careers/{$slp->id}")
        ->assertSee('Independent Contractor Opportunity')
        ->assertDontSee('FSCD Services')
        ->assertNoJavaScriptErrors();
});

it('reaches the application form from a posting', function () {
    $psych = Career::query()->where('position', 'Psychologist')->firstOrFail();

    visit("/careers/{$psych->id}")
        ->click('#posting-apply-top')
        ->assertPathIs("/careers/apply/{$psych->id}")
        ->assertNoJavaScriptErrors();
});

it('hides a posting the admin has deactivated', function () {
    Career::query()->where('position', 'Physiotherapist (PT)')->update(['is_active' => false]);

    visit('/careers')
        ->assertSee('Speech-Language Pathologist (SLP)')
        ->assertDontSee('Physiotherapist (PT)')
        ->assertNoJavaScriptErrors();
});

/**
 * The admin form and the posting page both map over the list columns, so a
 * posting whose lists were stored as newline strings used to throw
 * `values.map is not a function`.
 */
it('edits a posting whose lists were stored as newline strings', function () {
    $career = Career::query()->where('position', 'Psychologist')->firstOrFail();

    DB::table('careers')->where('id', $career->id)->update([
        'responsibilities' => json_encode("Assess clients\nWrite reports"),
        'qualifications' => json_encode("Alberta registration\nClear record check"),
    ]);

    $this->actingAs(adminUser());

    visit("/admin/careers/edit/{$career->id}")
        ->assertSee('Assess clients')
        ->assertSee('Alberta registration')
        ->assertNoJavaScriptErrors();
});

it('renders a posting page whose lists were stored as newline strings', function () {
    $career = Career::query()->where('position', 'Psychologist')->firstOrFail();

    DB::table('careers')->where('id', $career->id)->update([
        'responsibilities' => json_encode("Assess clients\nWrite reports"),
    ]);

    visit("/careers/{$career->id}")
        ->assertSee('Assess clients')
        ->assertSee('Write reports')
        ->assertNoJavaScriptErrors();
});
