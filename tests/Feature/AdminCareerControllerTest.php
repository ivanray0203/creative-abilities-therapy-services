<?php

use App\Models\Career;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validCareerPayload(array $overrides = []): array
{
    return array_merge([
        'position' => 'Occupational Therapist',
        'location' => 'Calgary, AB',
        'schedule' => 'Part-time',
        'contract' => 'Contract',
        'rate' => '$40-$60/hr',
        'short_description' => 'Join our growing team.',
        'about_description' => 'A detailed description of the role.',
        'responsibilities' => ['Assess clients', 'Deliver therapy sessions'],
        'qualifications' => ['Registered with CAOT'],
        'skills' => ['Communication'],
        'benefits' => ['Flexible hours'],
        'highlights' => ['Growing team'],
        'required_documents' => ['Resume', 'Cover Letter'],
        'is_active' => true,
        'due_date' => now()->addMonth()->toDateString(),
        'level' => 'Intermediate',
        'hours' => '20-30 hrs/week',
    ], $overrides);
}

test('a non-admin is redirected away from the careers admin routes', function () {
    $this->actingAs(therapistUser())->get('/admin/careers')->assertRedirect('/therapist');
});

test('the careers index lists positions with stats and filters', function () {
    Career::factory()->create(['position' => 'Occupational Therapist', 'is_active' => true]);
    Career::factory()->create(['position' => 'Speech-Language Pathologist', 'is_active' => false]);

    $response = $this->actingAs(adminUser())->get('/admin/careers');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/careers/index')
        ->where('stats.total', 2)
        ->where('stats.active', 1)
    );
});

test('an admin can create a position', function () {
    $payload = validCareerPayload();

    $response = $this->actingAs(adminUser())->post('/admin/careers', $payload);

    $response->assertRedirect('/admin/careers');
    $response->assertSessionHasNoErrors();

    $career = Career::first();
    expect($career)->not->toBeNull();
    expect($career->position)->toBe('Occupational Therapist');
    expect($career->responsibilities)->toBe(['Assess clients', 'Deliver therapy sessions']);
});

test('creating a position requires the required fields', function () {
    $response = $this->actingAs(adminUser())->post('/admin/careers', []);

    $response->assertSessionHasErrors([
        'position', 'location', 'schedule', 'contract', 'rate',
        'short_description', 'about_description',
    ]);

    expect(Career::count())->toBe(0);
});

test('an admin can update a position', function () {
    $career = Career::factory()->create(['position' => 'Old Title']);

    $response = $this->actingAs(adminUser())->put("/admin/careers/{$career->id}", validCareerPayload([
        'position' => 'Updated Title',
    ]));

    $response->assertRedirect('/admin/careers');
    $response->assertSessionHasNoErrors();

    expect($career->refresh()->position)->toBe('Updated Title');
});

test('an admin can delete a position', function () {
    $career = Career::factory()->create();

    $response = $this->actingAs(adminUser())->delete("/admin/careers/{$career->id}");

    $response->assertRedirect('/admin/careers');
    expect(Career::count())->toBe(0);
});

/**
 * The posting narrative (`detail`) is what the public posting page renders,
 * so the admin form has to round-trip every section of it.
 */
test('an admin can save the full posting narrative', function () {
    $detail = [
        'intro' => ['First intro paragraph.', 'Second intro paragraph.'],
        'role_summary' => 'What this role does day to day.',
        'responsibilities_lead_in' => 'Responsibilities may include:',
        'qualifications_lead_in' => 'Applicants should have:',
        'qualifications_note' => 'Related experience is considered an asset.',
        'collaboration' => [
            'title' => 'Multidisciplinary Collaboration',
            'intro' => 'You may collaborate with:',
            'lead_in' => 'This may include:',
            'items' => ['Occupational Therapists', 'Physiotherapists'],
            'closing' => 'Collaboration supports consistency.',
        ],
        'offers' => [
            ['title' => 'Competitive Contract Rates', 'description' => 'Starting at $50.00/hour.'],
            ['title' => 'Flexible Scheduling', 'description' => 'Set your own availability.'],
        ],
        'fscd' => [
            'title' => 'FSCD Services',
            'intro' => 'You may support FSCD-funded families.',
            'lead_in' => 'You may participate in:',
            'items' => ['Behavioural and Developmental Support (BDS)'],
            'closing' => 'Documentation requirements apply.',
        ],
        'extras' => [
            [
                'title' => 'Documentation & Team Communication',
                'paragraphs' => ['You keep session records.'],
                'lead_in' => 'This may include:',
                'items' => ['Session documentation', 'Timesheets'],
                'closing' => 'Reviewed during onboarding.',
            ],
        ],
        'contractor' => [
            'title' => 'Independent Contractor Opportunity',
            'paragraphs' => ['This is a contractor position.', 'Hours are not guaranteed.'],
        ],
        'closing_title' => 'Ready to Join Our Team?',
        'closing' => 'We would be happy to hear from you.',
    ];

    $response = $this->actingAs(adminUser())->post('/admin/careers', validCareerPayload([
        'sort_order' => 3,
        'detail' => $detail,
    ]));

    $response->assertRedirect('/admin/careers')->assertSessionHasNoErrors();

    $career = Career::query()->latest('id')->first();

    // `validated()` returns keys in rule order, so compare structure not order.
    $normalise = function (array $value) use (&$normalise): array {
        ksort($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $normalise($item);
            }
        }

        return $value;
    };

    expect($career->sort_order)->toBe(3)
        ->and($normalise($career->detail))->toBe($normalise($detail));
});

test('a posting without an FSCD section stores a null for it', function () {
    $this->actingAs(adminUser())->post('/admin/careers', validCareerPayload([
        'detail' => ['role_summary' => 'Summary.', 'fscd' => null],
    ]))->assertSessionHasNoErrors();

    expect(Career::query()->latest('id')->first()->detail['fscd'])->toBeNull();
});

test('the posting narrative rejects a malformed offer', function () {
    $response = $this->actingAs(adminUser())->post('/admin/careers', validCareerPayload([
        'detail' => ['offers' => [['title' => 'Missing its description']]],
    ]));

    $response->assertSessionHasErrors('detail.offers.0.description');
    expect(Career::query()->where('position', 'Occupational Therapist')->exists())->toBeFalse();
});
