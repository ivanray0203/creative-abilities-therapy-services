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
