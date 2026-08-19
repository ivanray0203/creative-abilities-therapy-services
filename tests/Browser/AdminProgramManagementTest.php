<?php

use App\Models\Program;
use App\Models\ProgramRegistration;

it('adds a program from the admin and publishes it to the public site', function () {
    $this->actingAs(adminUser());

    $page = visit('/admin/programs/add');

    $page->fill('#program-name', 'Winter Skills Group')
        ->fill('#program-category', 'Social Skills')
        ->fill('#program-summary', 'A short winter group for building confidence.')
        ->fill('#program-description', 'The full description of the winter group.')
        ->fill('#program-age-range', '6-9 years')
        ->fill('#program-capacity', '8')
        ->fill('#program-highlight-0', 'Maximum of 8 children')
        ->click('#program-submit')
        ->assertSee('Winter Skills Group')
        ->assertNoJavaScriptErrors();

    $program = Program::first();
    expect($program->slug)->toBe('winter-skills-group');

    visit('/programs')->assertSee('Winter Skills Group');
});

it('shows a program\'s registrations and confirms one', function () {
    $program = Program::factory()->create(['name' => 'Summer Sensory Camp']);
    $registration = ProgramRegistration::factory()->create([
        'program_id' => $program->id,
        'parent_name' => 'Sam Nolan',
        'participant_first_name' => 'Aria',
        'participant_last_name' => 'Nolan',
    ]);

    $this->actingAs(adminUser());

    $page = visit("/admin/programs/{$program->slug}");

    $page->assertSee('Aria Nolan')
        ->assertSee('Sam Nolan')
        ->assertSee($registration->reference_number)
        ->click("#registration-status-{$registration->id}")
        ->click('confirmed')
        ->assertNoJavaScriptErrors();

    expect($registration->refresh()->status)->toBe('confirmed');
});

it('warns that a program with registrations will be unpublished rather than deleted', function () {
    $program = Program::factory()->create(['name' => 'Summer Sensory Camp']);
    ProgramRegistration::factory()->create(['program_id' => $program->id]);

    $this->actingAs(adminUser());

    visit('/admin/programs')
        ->click('Delete')
        ->assertSee('Unpublish Program')
        ->assertSee('will be removed from the public site rather than deleted')
        ->click('#confirm-delete-program')
        ->assertNoJavaScriptErrors();

    expect(Program::count())->toBe(1);
    expect($program->refresh()->is_active)->toBeFalse();
});
