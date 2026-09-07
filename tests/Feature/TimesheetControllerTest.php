<?php

use App\Mail\TimesheetReadyMail;
use App\Mail\TimesheetSignedAdminNotification;
use App\Models\Client;
use App\Models\Intake;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\User;
use App\Services\PdfService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/** A child whose parent has a portal login, so they can sign. */
function familyWithParent(): Client
{
    $parent = User::factory()->client()->create();

    return Client::factory()->create([
        'user_id' => $parent->id,
        'original_intake_id' => Intake::factory()->create([
            'primary_parent_name' => 'Dana Reyes',
            'primary_parent_email' => 'dana@example.test',
            'funding_number' => 'FSCD-99812',
        ])->id,
    ])->load('user', 'originalIntake');
}

/*
|--------------------------------------------------------------------------
| Who reaches which half of the therapist portal
|--------------------------------------------------------------------------
*/

test('an aide gets Hours and Timesheets where a therapist gets Billing and Invoices', function () {
    $aide = aideUser();

    $this->actingAs($aide)->get('/therapist/hours')->assertOk();
    $this->actingAs($aide)->get('/therapist/timesheets')->assertOk();

    // Hiding the menu is not access control: the routes themselves are shut.
    $this->actingAs($aide)->get('/therapist/billing')->assertRedirect('/therapist/hours');
    $this->actingAs($aide)->get('/therapist/invoices')->assertRedirect('/therapist/hours');
});

test('a therapist who bills services cannot reach the aide screens', function () {
    $therapist = therapistUser();

    $this->actingAs($therapist)->get('/therapist/hours')->assertRedirect('/therapist/billing');
    $this->actingAs($therapist)->get('/therapist/timesheets')->assertRedirect('/therapist/billing');

    $this->actingAs($therapist)->get('/therapist/billing')->assertOk();
});

test('a therapist with no team-member record keeps the billing side', function () {
    // `position` is nullable and a therapist may have no record at all —
    // neither makes them an aide.
    $this->actingAs(therapistUser())->get('/therapist/billing')->assertOk();
    $this->actingAs(aideUser('Behaviour Aide'))->get('/therapist/hours')->assertOk();
    $this->actingAs(aideUser('Occupational Therapist'))->get('/therapist/billing')->assertOk();
});

/*
|--------------------------------------------------------------------------
| Logging hours
|--------------------------------------------------------------------------
*/

test('logging hours saves one row per day', function () {
    $aide = aideUser();
    $client = Client::factory()->create();

    $this->actingAs($aide)->post('/therapist/hours', [
        'client_id' => $client->id,
        'entries' => [
            [
                'entry_date' => '2026-08-03',
                'hourly_respite_hours' => 2,
                'community_support_hours' => 0,
                'bda_direct_hours' => 1.5,
                'bda_indirect_hours' => 0.25,
                'notes' => 'Home visit.',
            ],
            [
                'entry_date' => '2026-08-04',
                'hourly_respite_hours' => 0,
                'community_support_hours' => 3,
                'bda_direct_hours' => 0,
                'bda_indirect_hours' => 0,
            ],
        ],
    ])->assertRedirect('/therapist/hours')->assertSessionHasNoErrors();

    expect(TimesheetEntry::count())->toBe(2);

    $first = TimesheetEntry::query()->whereDate('entry_date', '2026-08-03')->firstOrFail();
    expect($first->therapist_id)->toBe($aide->id)
        ->and($first->client_id)->toBe($client->id)
        ->and((float) $first->bda_direct_hours)->toBe(1.5)
        ->and($first->totalHours())->toBe(1.75)
        ->and($first->timesheet_id)->toBeNull();
});

test('logging a day already logged for that child corrects it rather than duplicating it', function () {
    $aide = aideUser();
    $client = Client::factory()->create();

    $post = fn (float $hours) => $this->actingAs($aide)->post('/therapist/hours', [
        'client_id' => $client->id,
        'entries' => [['entry_date' => '2026-08-03', 'bda_direct_hours' => $hours]],
    ]);

    $post(2)->assertSessionHasNoErrors();
    $post(3.5)->assertSessionHasNoErrors();

    expect(TimesheetEntry::count())->toBe(1)
        ->and((float) TimesheetEntry::firstOrFail()->bda_direct_hours)->toBe(3.5);
});

test('a day with no hours in any column is rejected', function () {
    $aide = aideUser();
    $client = Client::factory()->create();

    $this->actingAs($aide)->post('/therapist/hours', [
        'client_id' => $client->id,
        'entries' => [['entry_date' => '2026-08-03']],
    ])->assertSessionHasErrors('entries.0.hourly_respite_hours');

    expect(TimesheetEntry::count())->toBe(0);
});

test('the same date twice on one form is rejected', function () {
    $aide = aideUser();
    $client = Client::factory()->create();

    $this->actingAs($aide)->post('/therapist/hours', [
        'client_id' => $client->id,
        'entries' => [
            ['entry_date' => '2026-08-03', 'bda_direct_hours' => 1],
            ['entry_date' => '2026-08-03', 'bda_direct_hours' => 2],
        ],
    ])->assertSessionHasErrors('entries.1.entry_date');
});

test('an aide only sees their own logged hours', function () {
    $aide = aideUser();
    TimesheetEntry::factory()->create(['therapist_id' => $aide->id]);
    TimesheetEntry::factory()->create(['therapist_id' => aideUser()->id]);

    $this->actingAs($aide)->get('/therapist/hours')
        ->assertInertia(fn ($page) => $page
            ->component('hours/index')
            ->has('entries.data', 1)
            ->where('entries.data.0.therapist_id', $aide->id)
        );
});

test('hours already on a timesheet can no longer be removed', function () {
    $aide = aideUser();
    $timesheet = Timesheet::factory()->create(['therapist_id' => $aide->id]);
    $claimed = TimesheetEntry::factory()->claimed($timesheet->id)->create(['therapist_id' => $aide->id]);
    $loose = TimesheetEntry::factory()->create(['therapist_id' => $aide->id]);

    $this->actingAs($aide)->delete("/therapist/hours/{$claimed->id}")->assertSessionHas('error');
    expect($claimed->fresh())->not->toBeNull();

    $this->actingAs($aide)->delete("/therapist/hours/{$loose->id}")->assertSessionHas('success');
    expect($loose->fresh())->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Generating the form
|--------------------------------------------------------------------------
*/

test('generating claims the unclaimed hours in range, totals them and mails the parent', function () {
    Storage::fake('public');
    Mail::fake();

    $aide = aideUser();
    $client = familyWithParent();

    $inRange = collect([
        ['entry_date' => '2026-08-03', 'hourly_respite_hours' => 2, 'bda_direct_hours' => 1.5, 'bda_indirect_hours' => 0.25],
        ['entry_date' => '2026-08-10', 'community_support_hours' => 3, 'bda_direct_hours' => 1],
    ])->map(fn (array $row) => TimesheetEntry::factory()->create([
        ...$row,
        'therapist_id' => $aide->id,
        'client_id' => $client->id,
        'hourly_respite_hours' => $row['hourly_respite_hours'] ?? 0,
        'community_support_hours' => $row['community_support_hours'] ?? 0,
        'bda_direct_hours' => $row['bda_direct_hours'] ?? 0,
        'bda_indirect_hours' => $row['bda_indirect_hours'] ?? 0,
    ]));

    // Outside the period, and another child's day inside it — neither belongs
    // on this form.
    $outOfRange = TimesheetEntry::factory()->create([
        'therapist_id' => $aide->id,
        'client_id' => $client->id,
        'entry_date' => '2026-09-01',
    ]);
    $otherChild = TimesheetEntry::factory()->create([
        'therapist_id' => $aide->id,
        'entry_date' => '2026-08-05',
    ]);

    $this->actingAs($aide)->post('/therapist/timesheets/generate', [
        'client_id' => $client->id,
        'date_start' => '2026-08-01',
        'date_end' => '2026-08-31',
        'signature' => signaturePng(),
    ])->assertSessionHasNoErrors();

    $timesheet = Timesheet::query()->sole();

    expect($timesheet->therapist_id)->toBe($aide->id)
        ->and($timesheet->client_id)->toBe($client->id)
        ->and($timesheet->timesheet_number)->toStartWith('TMS-')
        ->and($timesheet->status)->toBe(Timesheet::STATUS_AWAITING_CLIENT)
        ->and($timesheet->aide_signature)->toBe(signaturePng())
        ->and($timesheet->aide_signed_at)->not->toBeNull()
        ->and($timesheet->parent_signature)->toBeNull()
        ->and((float) $timesheet->total_hourly_respite)->toBe(2.0)
        ->and((float) $timesheet->total_community_support)->toBe(3.0)
        ->and((float) $timesheet->total_bda_direct)->toBe(2.5)
        ->and((float) $timesheet->total_bda_indirect)->toBe(0.25)
        ->and((float) $timesheet->total_hours)->toBe(2.75)
        ->and($timesheet->rows)->toHaveCount(2)
        ->and($timesheet->not_signed_timesheet)->not->toBeNull();

    $inRange->each(fn (TimesheetEntry $entry) => expect($entry->fresh()->timesheet_id)->toBe($timesheet->id));
    expect($outOfRange->fresh()->timesheet_id)->toBeNull()
        ->and($otherChild->fresh()->timesheet_id)->toBeNull();

    // The portal login is where it goes; the intake's parent email is only
    // the fallback for a family with no account yet.
    Mail::assertQueued(TimesheetReadyMail::class, fn ($mail) => $mail->hasTo($client->user->email));
});

test('generating the same period twice finds nothing left to sheet', function () {
    Storage::fake('public');
    Mail::fake();

    $aide = aideUser();
    $client = familyWithParent();
    TimesheetEntry::factory()->create([
        'therapist_id' => $aide->id,
        'client_id' => $client->id,
        'entry_date' => '2026-08-03',
    ]);

    $generate = fn () => $this->actingAs($aide)->post('/therapist/timesheets/generate', [
        'client_id' => $client->id,
        'date_start' => '2026-08-01',
        'date_end' => '2026-08-31',
        'signature' => signaturePng(),
    ]);

    $generate()->assertSessionHasNoErrors();
    $generate()->assertSessionHasErrors('client_id');

    expect(Timesheet::count())->toBe(1);
});

test('generating needs a real signature', function () {
    $aide = aideUser();
    $client = familyWithParent();
    TimesheetEntry::factory()->create(['therapist_id' => $aide->id, 'client_id' => $client->id]);

    $this->actingAs($aide)->post('/therapist/timesheets/generate', [
        'client_id' => $client->id,
        'date_start' => '2026-08-01',
        'date_end' => '2026-08-31',
        'signature' => 'data:image/png;base64,'.base64_encode('<svg/>'),
    ])->assertSessionHasErrors('signature');

    expect(Timesheet::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Signing
|--------------------------------------------------------------------------
*/

test('the parent signs, which files the signed copy and tells the admins', function () {
    Storage::fake('public');
    Mail::fake();

    $admin = adminUser();
    $client = familyWithParent();
    $timesheet = Timesheet::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)
        ->post("/client/timesheets/{$timesheet->id}/sign", ['signature' => signaturePng()])
        ->assertSessionHas('success');

    $timesheet->refresh();

    expect($timesheet->status)->toBe(Timesheet::STATUS_SIGNED)
        ->and($timesheet->parent_signature)->toBe(signaturePng())
        ->and($timesheet->parent_signed_at)->not->toBeNull()
        ->and($timesheet->signed_timesheet)->not->toBeNull()
        ->and(collect($timesheet->timeline)->pluck('title'))->toContain('Timesheet signed by parent');

    Mail::assertQueued(TimesheetSignedAdminNotification::class, fn ($mail) => $mail->hasTo($admin->email));
});

test('a signed timesheet cannot be signed again', function () {
    Storage::fake('public');

    $client = familyWithParent();
    $timesheet = Timesheet::factory()->signed()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)
        ->post("/client/timesheets/{$timesheet->id}/sign", ['signature' => signaturePng()])
        ->assertNotFound();
});

test('another family cannot open or sign a timesheet that is not theirs', function () {
    Storage::fake('public');

    $timesheet = Timesheet::factory()->create(['client_id' => familyWithParent()->id]);
    $stranger = familyWithParent();

    $this->actingAs($stranger->user)->get("/client/timesheets/{$timesheet->id}")->assertNotFound();
    $this->actingAs($stranger->user)->get("/client/timesheets/{$timesheet->id}/pdf")->assertNotFound();
    $this->actingAs($stranger->user)
        ->post("/client/timesheets/{$timesheet->id}/sign", ['signature' => signaturePng()])
        ->assertNotFound();
});

test('an aide cannot open another aide\'s timesheet', function () {
    $timesheet = Timesheet::factory()->create();

    $this->actingAs(aideUser())->get("/therapist/timesheets/{$timesheet->id}")->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| What each side sees
|--------------------------------------------------------------------------
*/

test('the admin list opens on the signed forms and can be widened', function () {
    $signed = Timesheet::factory()->signed()->create();
    Timesheet::factory()->create();

    $this->actingAs(adminUser())->get('/admin/timesheets')
        ->assertInertia(fn ($page) => $page
            ->component('timesheets/index')
            ->where('filters.status', Timesheet::STATUS_SIGNED)
            ->has('timesheets.data', 1)
            ->where('timesheets.data.0.id', $signed->id)
            ->where('stats.awaiting_client', 1)
            ->where('stats.signed', 1)
        );

    $this->actingAs(adminUser())->get('/admin/timesheets?status=all')
        ->assertInertia(fn ($page) => $page->has('timesheets.data', 2));
});

test('the aide and admin lists gather a family together, alphabetically by child', function () {
    $aide = aideUser();

    $named = function (string $first) use ($aide): Client {
        $client = Client::factory()->create([
            'original_intake_id' => Intake::factory()->create([
                'child_first_name' => $first,
                'child_last_name' => 'Blake',
            ])->id,
        ]);

        // Two forms each, generated out of order, so grouping cannot be the
        // accident of them having been created together.
        Timesheet::factory()->count(2)->create([
            'therapist_id' => $aide->id,
            'client_id' => $client->id,
        ]);

        return $client;
    };

    $zara = $named('Zara');
    $adam = $named('Adam');

    $this->actingAs($aide)->get('/therapist/timesheets')
        ->assertInertia(fn ($page) => $page
            ->where('groupedByClient', true)
            ->has('timesheets.data', 4)
            // Adam's pair first, then Zara's — never interleaved.
            ->where('timesheets.data.0.client_id', $adam->id)
            ->where('timesheets.data.1.client_id', $adam->id)
            ->where('timesheets.data.2.client_id', $zara->id)
            ->where('timesheets.data.3.client_id', $zara->id)
            // The join must not overwrite the timesheet's own id.
            ->where('timesheets.data.0.timesheet_number', fn ($number) => str_starts_with($number, 'TMS-'))
        );
});

test('a parent sees their list ungrouped, so there is no heading over one child', function () {
    $client = familyWithParent();
    Timesheet::factory()->create(['client_id' => $client->id]);

    $this->actingAs($client->user)->get('/client/timesheets')
        ->assertInertia(fn ($page) => $page->where('groupedByClient', false));
});

test('a parent sees only the child the portal is scoped to', function () {
    $client = familyWithParent();
    $mine = Timesheet::factory()->create(['client_id' => $client->id]);
    Timesheet::factory()->create();

    $this->actingAs($client->user)->get('/client/timesheets')
        ->assertInertia(fn ($page) => $page
            ->has('timesheets.data', 1)
            ->where('timesheets.data.0.id', $mine->id)
        );
});

test('the generate modal is offered only children with hours still waiting', function () {
    $aide = aideUser();
    $waiting = Client::factory()->create();
    $done = Client::factory()->create();

    TimesheetEntry::factory()->create(['therapist_id' => $aide->id, 'client_id' => $waiting->id]);
    TimesheetEntry::factory()
        ->claimed(Timesheet::factory()->create(['therapist_id' => $aide->id])->id)
        ->create(['therapist_id' => $aide->id, 'client_id' => $done->id]);

    $this->actingAs($aide)->get('/therapist/timesheets')
        ->assertInertia(fn ($page) => $page
            ->has('sheetableClients', 1)
            ->where('sheetableClients.0.id', $waiting->id)
        );
});

/*
|--------------------------------------------------------------------------
| The document
|--------------------------------------------------------------------------
*/

test('the time sheet renders as a PDF', function () {
    $client = familyWithParent();
    $timesheet = Timesheet::factory()->create(['client_id' => $client->id]);

    $contents = app(PdfService::class)->timesheet($timesheet);

    expect($contents)->toStartWith('%PDF');
});

test('the printed form carries the child, DOB and FSCD file number off the intake', function () {
    $client = familyWithParent();
    $client->originalIntake->update([
        'child_first_name' => 'Rowan',
        'child_last_name' => 'Blake',
        'date_of_birth' => '2019-04-11',
    ]);
    $aide = aideUser();
    $timesheet = Timesheet::factory()->create([
        'client_id' => $client->id,
        'therapist_id' => $aide->id,
    ]);

    // Asserted on the rendered Blade rather than the PDF bytes: dompdf's
    // output is compressed, so the text is not readable there.
    $html = view('pdf.timesheet', [
        'timesheet' => $timesheet->fresh()->load('client.originalIntake', 'therapist'),
        'logoPath' => public_path('CatsLogo/web-app-manifest-192x192.png'),
        'clientDetails' => [
            'name' => $timesheet->client->displayName(),
            'date_of_birth' => '2019-Apr-11',
            'fscd_file_number' => 'FSCD-99812',
        ],
        'aideName' => $aide->full_name,
        'parentName' => 'Dana Reyes',
        'aideSignature' => $timesheet->aide_signature,
        'parentSignature' => null,
    ])->render();

    expect($html)->toContain('Rowan Blake')
        ->toContain('2019-Apr-11')
        ->toContain('FSCD-99812')
        ->toContain($aide->full_name)
        ->toContain('Dana Reyes')
        ->toContain('Awaiting signature');
});

test('the pdf route serves the form to everyone party to it', function () {
    Storage::fake('public');

    $aide = aideUser();
    $client = familyWithParent();
    $timesheet = Timesheet::factory()->create([
        'therapist_id' => $aide->id,
        'client_id' => $client->id,
    ]);

    foreach ([
        [$aide, "/therapist/timesheets/{$timesheet->id}/pdf"],
        [$client->user, "/client/timesheets/{$timesheet->id}/pdf"],
        [adminUser(), "/admin/timesheets/{$timesheet->id}/pdf"],
    ] as [$user, $url]) {
        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
});

test('deleting a timesheet releases its hours back to the aide', function () {
    $aide = aideUser();
    $timesheet = Timesheet::factory()->create(['therapist_id' => $aide->id]);
    $entry = TimesheetEntry::factory()->claimed($timesheet->id)->create(['therapist_id' => $aide->id]);

    $this->actingAs(adminUser())->delete("/admin/timesheets/{$timesheet->id}")
        ->assertRedirect('/admin/timesheets');

    expect(Timesheet::count())->toBe(0)
        ->and($entry->fresh()->timesheet_id)->toBeNull();
});

test('only an admin may delete a timesheet', function () {
    $aide = aideUser();
    $timesheet = Timesheet::factory()->create(['therapist_id' => $aide->id]);

    $this->actingAs($aide)->delete("/admin/timesheets/{$timesheet->id}")->assertRedirect();

    expect(Timesheet::count())->toBe(1);
});
