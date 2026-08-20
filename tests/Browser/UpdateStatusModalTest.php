<?php

use App\Models\Intake;
use App\Models\User;

/**
 * The update-status modal renders one therapist picker per requested service,
 * so a multi-service intake grows taller than the screen.
 *
 * DialogContent is centred with `translate-y-[-50%]`, so without a max-height
 * the overflow spills past both edges of the viewport and the Confirm button
 * can't be reached — `overflow-y-auto` does nothing until the height is
 * capped.
 *
 * Measurements use offsetHeight/scrollHeight rather than
 * getBoundingClientRect where possible, because the entrance animation
 * (`slide-in-from-top-[48%]`) transforms the rect while it plays.
 */

/**
 * Reported against 3 services on a laptop screen. The headless viewport is
 * taller than that, so the fixture uses enough services to clear it — the
 * assertions below verify the content genuinely overflows rather than the
 * test passing trivially.
 */
function intakeNeedingManyServices(): Intake
{
    return Intake::factory()->create([
        'status' => 'under_review',
        'services_needed' => [
            'Speech and Language Therapy',
            'Occupational Therapy',
            'Physical Therapy',
            'Behavioural Therapy',
            'Music Therapy',
            'Art Therapy',
            'Play Therapy',
            'Social Skills Group',
        ],
    ]);
}

it('caps the modal to the viewport when an intake needs several services', function () {
    $admin = User::factory()->admin()->create();
    $intake = intakeNeedingManyServices();

    $this->actingAs($admin);

    $page = visit("/admin/intake/{$intake->id}");

    // "Change Status to Approved" is the dialog's own title — asserting on a
    // service name here would pass off the page behind it.
    $page->click('Change Status')
        ->click('Assign to Therapist')
        ->assertSee('Change Status to Approved');

    $metrics = json_decode((string) $page->script(
        <<<'JS'
        (() => {
            const dialog = document.querySelector('[role="dialog"]');

            return JSON.stringify({
                rendered: dialog.offsetHeight,
                content: dialog.scrollHeight,
                viewport: window.innerHeight,
                overflowY: getComputedStyle(dialog).overflowY,
            });
        })()
        JS
    ), true);

    // The modal never grows past the screen...
    expect($metrics['rendered'])->toBeLessThanOrEqual($metrics['viewport']);

    // ...its content really does exceed that cap, so this exercises the
    // overflow case rather than passing trivially...
    expect($metrics['content'])->toBeGreaterThan($metrics['rendered']);

    // ...and the excess is reachable by scrolling.
    expect($metrics['overflowY'])->toBe('auto');
});

/*
 * A second test asserting the Confirm button's on-screen position was tried
 * and removed: getBoundingClientRect is skewed by the dialog's entrance
 * animation, so it passed with and without the fix depending on timing. The
 * layout-based assertions above are immune to that and do discriminate —
 * unfixed, the dialog renders 1494px tall in a 1117px viewport.
 */
