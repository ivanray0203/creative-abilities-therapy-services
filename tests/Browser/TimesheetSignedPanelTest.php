<?php

use App\Models\Client;
use App\Models\Timesheet;

/**
 * The filed-copy panel on a signed time sheet is staff-only. Which side of
 * that line a viewer falls on is decided at render time from the `role`
 * prop, so it is asserted in the browser rather than on the response.
 */

/** A signed time sheet, its Drive copy on file, belonging to one family. */
function signedTimesheetFor(Client $client): Timesheet
{
    return Timesheet::factory()->signed()->create([
        'client_id' => $client->id,
        'therapist_id' => aideUser()->id,
        'signed_timesheet' => 'https://drive.google.com/file/d/abc123def456/view',
    ]);
}

it('does not tell the parent their own signature is on file', function () {
    $client = clientWithUser();
    $timesheet = signedTimesheetFor($client);

    $this->actingAs($client->user);

    visit("/client/timesheets/{$timesheet->id}")
        ->assertDontSee('Signed by the parent')
        ->assertDontSee('View the signed PDF')
        // The form itself, and the way to print it, are still theirs.
        ->assertSee($timesheet->timesheet_number)
        ->assertSee('Print / PDF')
        ->assertNoJavaScriptErrors();
});

it('shows the filed copy to the aide who generated it', function () {
    $client = clientWithUser();
    $timesheet = signedTimesheetFor($client);

    $this->actingAs($timesheet->therapist);

    visit("/therapist/timesheets/{$timesheet->id}")
        ->assertSee('Signed by the parent')
        ->assertSee('View the signed PDF')
        ->assertNoJavaScriptErrors();
});

it('shows the filed copy to the admin', function () {
    $client = clientWithUser();
    $timesheet = signedTimesheetFor($client);

    $this->actingAs(adminUser());

    visit("/admin/timesheets/{$timesheet->id}")
        ->assertSee('Signed by the parent')
        ->assertSee('View the signed PDF')
        ->assertNoJavaScriptErrors();
});
