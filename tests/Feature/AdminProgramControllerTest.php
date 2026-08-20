<?php

use App\Models\Program;
use App\Models\ProgramRegistration;

test('the program list is admin-only', function () {
    $this->actingAs(therapistUser())->get('/admin/programs')->assertRedirect('/therapist');
});

test('the list shows programs with their taken places', function () {
    $program = Program::factory()->create(['capacity' => 10]);
    ProgramRegistration::factory()->count(3)->create(['program_id' => $program->id]);
    ProgramRegistration::factory()->cancelled()->create(['program_id' => $program->id]);

    $this->actingAs(adminUser())->get('/admin/programs')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/programs/index')
            ->has('programs.data', 1)
            // Cancelled sign-ups free their place but stay on the record.
            ->where('programs.data.0.taken_places', 3)
            ->where('programs.data.0.registrations_count', 4)
            ->where('stats.registrations', 3)
        );
});

test('the list filters by name and published status', function () {
    Program::factory()->create(['name' => 'Summer Sensory Camp']);
    Program::factory()->inactive()->create(['name' => 'Retired Group']);

    $this->actingAs(adminUser())->get('/admin/programs?search=Summer')
        ->assertInertia(fn ($page) => $page->has('programs.data', 1));

    $this->actingAs(adminUser())->get('/admin/programs?status=inactive')
        ->assertInertia(fn ($page) => $page
            ->has('programs.data', 1)
            ->where('programs.data.0.name', 'Retired Group')
        );
});

test('an admin can add a program and its slug comes from the name', function () {
    $this->actingAs(adminUser())->post('/admin/programs', [
        'name' => 'Winter Skills Group',
        'summary' => 'A short winter group.',
        'description' => 'The full description.',
        'highlights' => ['Small group', ''],
        'capacity' => 8,
        'price' => 120,
        'is_active' => true,
    ])->assertSessionHasNoErrors()->assertRedirect('/admin/programs');

    $program = Program::first();
    expect($program->slug)->toBe('winter-skills-group');
    // Blank repeater rows are dropped rather than stored as empty bullets.
    expect($program->highlights)->toBe(['Small group']);
});

test('two programs cannot share a name', function () {
    Program::factory()->create(['name' => 'Winter Skills Group', 'slug' => 'winter-skills-group']);

    $this->actingAs(adminUser())->post('/admin/programs', [
        'name' => 'Winter Skills Group',
        'summary' => 'A duplicate.',
        'description' => 'The full description.',
    ])->assertSessionHasErrors('slug');

    expect(Program::count())->toBe(1);
});

test('editing a program keeps its own slug valid', function () {
    $program = Program::factory()->create(['name' => 'Winter Skills Group', 'slug' => 'winter-skills-group']);

    $this->actingAs(adminUser())->put("/admin/programs/{$program->slug}", [
        'name' => 'Winter Skills Group',
        'summary' => 'An updated summary.',
        'description' => 'The full description.',
    ])->assertSessionHasNoErrors();

    expect($program->refresh()->summary)->toBe('An updated summary.');
});

test('the end date cannot fall before the start, nor registration close after it', function () {
    $this->actingAs(adminUser())->post('/admin/programs', [
        'name' => 'Backwards Program',
        'summary' => 'Summary.',
        'description' => 'Description.',
        'starts_on' => '2027-03-10',
        'ends_on' => '2027-03-01',
        'registration_closes_on' => '2027-03-20',
    ])->assertSessionHasErrors(['ends_on', 'registration_closes_on']);

    expect(Program::count())->toBe(0);
});

test('the detail page lists the families registered', function () {
    $program = Program::factory()->create();
    $registration = ProgramRegistration::factory()->create([
        'program_id' => $program->id,
        'parent_name' => 'Sam Nolan',
    ]);

    $this->actingAs(adminUser())->get("/admin/programs/{$program->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/programs/show')
            ->has('registrations', 1)
            ->where('registrations.0.reference_number', $registration->reference_number)
            ->where('registrations.0.parent_name', 'Sam Nolan')
        );
});

test('an admin can move a registration to confirmed', function () {
    $program = Program::factory()->create();
    $registration = ProgramRegistration::factory()->create(['program_id' => $program->id]);

    $this->actingAs(adminUser())
        ->put("/admin/programs/{$program->slug}/registrations/{$registration->id}", ['status' => 'confirmed'])
        ->assertSessionHasNoErrors();

    expect($registration->refresh()->status)->toBe('confirmed');
});

test('a registration cannot be moved through a program it does not belong to', function () {
    $program = Program::factory()->create();
    $other = Program::factory()->create();
    $registration = ProgramRegistration::factory()->create(['program_id' => $other->id]);

    $this->actingAs(adminUser())
        ->put("/admin/programs/{$program->slug}/registrations/{$registration->id}", ['status' => 'confirmed'])
        ->assertNotFound();

    expect($registration->refresh()->status)->toBe('pending');
});

test('a program with no registrations is deleted outright', function () {
    $program = Program::factory()->create();

    $this->actingAs(adminUser())->delete("/admin/programs/{$program->slug}")
        ->assertRedirect('/admin/programs');

    expect(Program::count())->toBe(0);
});

test('a program families have registered for is unpublished rather than deleted', function () {
    $program = Program::factory()->create();
    ProgramRegistration::factory()->create(['program_id' => $program->id]);

    $this->actingAs(adminUser())->delete("/admin/programs/{$program->slug}")
        ->assertRedirect('/admin/programs');

    expect(Program::count())->toBe(1);
    expect($program->refresh()->is_active)->toBeFalse();
    expect(ProgramRegistration::count())->toBe(1);
});

test('unpublishing from the admin removes it from the public listing', function () {
    $program = Program::factory()->create();

    $this->actingAs(adminUser())->put("/admin/programs/{$program->slug}", [
        'name' => $program->name,
        'summary' => $program->summary,
        'description' => $program->description,
        'is_active' => false,
    ])->assertSessionHasNoErrors();

    $this->get('/programs')->assertInertia(fn ($page) => $page->has('programs', 0));
    $this->get("/programs/{$program->slug}")->assertNotFound();
});
