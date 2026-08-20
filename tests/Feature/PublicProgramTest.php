<?php

use App\Mail\ProgramRegistrationAdminNotification;
use App\Models\Program;
use App\Models\ProgramRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('the programs page lists published programs only', function () {
    $published = Program::factory()->create(['name' => 'Social Skills Group']);
    Program::factory()->inactive()->create(['name' => 'Retired Program']);

    $this->get('/programs')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/programs')
            ->has('programs', 1)
            ->where('programs.0.slug', $published->slug)
        );
});

test('a program page carries its description, highlights and remaining places', function () {
    $program = Program::factory()->create([
        'name' => 'Summer Sensory Camp',
        'highlights' => ['One staff member for every three children'],
        'capacity' => 10,
    ]);
    ProgramRegistration::factory()->count(4)->create(['program_id' => $program->id]);
    // A cancelled registration frees its place again.
    ProgramRegistration::factory()->cancelled()->create(['program_id' => $program->id]);

    $this->get("/programs/{$program->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/program-detail')
            ->where('program.name', 'Summer Sensory Camp')
            ->has('program.description')
            ->has('program.highlights', 1)
            ->where('program.places_left', 6)
            ->where('program.is_open', true)
        );
});

test('an unpublished program is not reachable by guessing its slug', function () {
    $program = Program::factory()->inactive()->create();

    $this->get("/programs/{$program->slug}")->assertNotFound();
});

test('a guest can register a child and gets a reference number back', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $program = Program::factory()->create();

    $this->post("/programs/{$program->slug}/register", [
        'participant_first_name' => 'Aria',
        'participant_last_name' => 'Nolan',
        'participant_date_of_birth' => '2019-04-02',
        'parent_name' => 'Sam Nolan',
        'parent_email' => 'sam@example.com',
        'parent_phone' => '555-0100',
        'notes' => 'Peanut allergy.',
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    $registration = ProgramRegistration::first();
    expect($registration)->not->toBeNull();
    expect($registration->program_id)->toBe($program->id);
    expect($registration->status)->toBe('pending');
    expect($registration->reference_number)->toStartWith('PRG-'.now()->year.'-');

    Mail::assertQueued(
        ProgramRegistrationAdminNotification::class,
        fn ($mail) => $mail->hasTo($admin->email),
    );
});

test('registration requires the contact fields', function () {
    $program = Program::factory()->create();

    $this->post("/programs/{$program->slug}/register", [])
        ->assertSessionHasErrors([
            'participant_first_name',
            'participant_last_name',
            'parent_name',
            'parent_email',
            'parent_phone',
        ]);

    expect(ProgramRegistration::count())->toBe(0);
});

test('a full program refuses a registration even though the page said it was open', function () {
    $program = Program::factory()->full()->create();
    ProgramRegistration::factory()->create(['program_id' => $program->id]);

    $this->post("/programs/{$program->slug}/register", [
        'participant_first_name' => 'Aria',
        'participant_last_name' => 'Nolan',
        'parent_name' => 'Sam Nolan',
        'parent_email' => 'sam@example.com',
        'parent_phone' => '555-0100',
    ])->assertSessionHasErrors('program');

    expect(ProgramRegistration::count())->toBe(1);
});

test('a program past its closing date refuses a registration', function () {
    $program = Program::factory()->closed()->create();

    $this->post("/programs/{$program->slug}/register", [
        'participant_first_name' => 'Aria',
        'participant_last_name' => 'Nolan',
        'parent_name' => 'Sam Nolan',
        'parent_email' => 'sam@example.com',
        'parent_phone' => '555-0100',
    ])->assertSessionHasErrors('program');

    expect(ProgramRegistration::count())->toBe(0);
});

test('the same child cannot be registered for the same program twice', function () {
    $program = Program::factory()->create();

    $payload = [
        'participant_first_name' => 'Aria',
        'participant_last_name' => 'Nolan',
        'parent_name' => 'Sam Nolan',
        'parent_email' => 'sam@example.com',
        'parent_phone' => '555-0100',
    ];

    $this->post("/programs/{$program->slug}/register", $payload)->assertSessionHasNoErrors();
    $this->post("/programs/{$program->slug}/register", $payload)
        ->assertSessionHasErrors('participant_first_name');

    expect(ProgramRegistration::count())->toBe(1);
});

test('the same child can register for a different program', function () {
    $first = Program::factory()->create();
    $second = Program::factory()->create();

    $payload = [
        'participant_first_name' => 'Aria',
        'participant_last_name' => 'Nolan',
        'parent_name' => 'Sam Nolan',
        'parent_email' => 'sam@example.com',
        'parent_phone' => '555-0100',
    ];

    $this->post("/programs/{$first->slug}/register", $payload)->assertSessionHasNoErrors();
    $this->post("/programs/{$second->slug}/register", $payload)->assertSessionHasNoErrors();

    expect(ProgramRegistration::count())->toBe(2);
});

test('the admin notification renders', function () {
    $registration = ProgramRegistration::factory()->create([
        'notes' => 'Peanut allergy.',
    ]);

    $rendered = (new ProgramRegistrationAdminNotification($registration->load('program')))->render();

    expect($rendered)->toContain($registration->reference_number)
        ->toContain($registration->participantName())
        ->toContain('Peanut allergy.');
});
