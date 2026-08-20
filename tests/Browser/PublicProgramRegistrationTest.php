<?php

use App\Models\Program;
use App\Models\ProgramRegistration;

it('reaches programs from the public nav and opens one', function () {
    Program::factory()->create(['name' => 'Social Skills Group']);

    visit('/')
        ->click('Programs')
        ->assertSee('Our Programs')
        ->assertSee('Social Skills Group')
        ->click('View & Register')
        ->assertSee('About this program')
        ->assertSee('Register')
        ->assertNoJavaScriptErrors();
});

it('registers a child and confirms with a reference number', function () {
    $program = Program::factory()->create(['name' => 'Summer Sensory Camp']);

    $page = visit("/programs/{$program->slug}");

    $page->fill('#participant-first-name', 'Aria')
        ->fill('#participant-last-name', 'Nolan')
        ->fill('#parent-name', 'Sam Nolan')
        ->fill('#parent-email', 'sam@example.com')
        ->fill('#parent-phone', '555-0100')
        ->fill('#registration-notes', 'Peanut allergy.')
        ->click('#program-register-submit')
        ->assertSee('Registration received')
        ->assertSee('PRG-'.now()->year)
        ->assertNoJavaScriptErrors();

    $registration = ProgramRegistration::first();
    expect($registration->participantName())->toBe('Aria Nolan');
    expect($registration->program_id)->toBe($program->id);
});

it('shows validation errors rather than silently failing', function () {
    $program = Program::factory()->create();

    visit("/programs/{$program->slug}")
        ->click('#program-register-submit')
        ->assertSee('The participant first name field is required.')
        ->assertNoJavaScriptErrors();

    expect(ProgramRegistration::count())->toBe(0);
});

it('replaces the form with a closed notice once registration has shut', function () {
    $program = Program::factory()->closed()->create();

    visit("/programs/{$program->slug}")
        ->assertSee('Registration is closed')
        ->assertDontSee('#program-register-submit')
        ->assertNoJavaScriptErrors();
});
