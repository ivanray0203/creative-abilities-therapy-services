<?php

use App\Models\Career;

it('shows a live list of missing required fields under the progress bar', function () {
    $career = Career::factory()->create();

    $page = visit("/careers/apply/{$career->id}");

    $page->assertSee('Application Progress')
        ->assertSee('Still missing')
        ->assertSee('First Name');
});
