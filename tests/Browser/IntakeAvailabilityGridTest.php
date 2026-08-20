<?php

it('captures availability as a day by time-of-day grid', function () {
    $page = visit('/intake/apply')
        ->click('Decline')
        ->click('Availability');

    $page->assertSee('Mondays')
        ->assertSee('Sundays')
        ->assertSee('Mornings (8am-11am)')
        ->assertSee('Afternoons (12pm-3pm)')
        ->assertSee('Evenings (4pm-7pm)')
        // One checkbox per day/time cell: 7 days x 3 times.
        ->assertCount('#availability-grid input[type=checkbox]', 21);
});

it('ticks a single day and time cell independently', function () {
    $page = visit('/intake/apply')
        ->click('Decline')
        ->click('Availability');

    $page->click('#availability-wednesday-evenings-4pm-7pm')
        ->assertChecked('#availability-wednesday-evenings-4pm-7pm')
        // The same time on another day stays untouched.
        ->assertNotChecked('#availability-monday-evenings-4pm-7pm')
        // As does another time on the same day.
        ->assertNotChecked('#availability-wednesday-mornings-8am-11am');
});
