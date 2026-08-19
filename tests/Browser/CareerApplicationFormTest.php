<?php

use App\Models\Career;

it('shows a live list of missing required fields under the progress bar', function () {
    $career = Career::factory()->create();

    $page = visit("/careers/apply/{$career->id}");

    $page->assertSee('Application Progress')
        ->assertSee('Still missing')
        ->assertSee('First Name');
});

/**
 * City is a dependent dropdown scoped to the chosen province, matching the
 * intake form — so province has to come first and city stays disabled until
 * there is a province to scope it by.
 */
it('offers cities only once a province has been picked', function () {
    $career = Career::factory()->create();

    $page = visit("/careers/apply/{$career->id}")
        ->click('Decline')
        ->click('#applicant-info-trigger');

    // Disabled until a province scopes the list. Asserted by probing rather
    // than clicking — a click on a disabled element never resolves.
    $disabled = $page->script(
        "document.querySelector('#city').hasAttribute('disabled')",
    );
    expect($disabled)->toBeTrue();

    $page->click('#province')
        ->click('Alberta')
        ->click('#city')
        ->assertSee('Calgary')
        ->assertSee('Lethbridge')
        // A city from another province is not offered.
        ->assertDontSee('Winnipeg')
        ->click('Calgary')
        ->assertSeeIn('#city', 'Calgary')
        ->assertNoJavaScriptErrors();
});

it('clears a chosen city when the province changes', function () {
    $career = Career::factory()->create();

    $page = visit("/careers/apply/{$career->id}")
        ->click('Decline')
        ->click('#applicant-info-trigger');

    $page->click('#province')
        ->click('Alberta')
        ->click('#city')
        ->click('Calgary')
        ->assertSeeIn('#city', 'Calgary');

    // Calgary is not in Manitoba, so the stale choice has to go.
    $page->click('#province')
        ->click('Manitoba')
        ->assertSeeIn('#city', 'Select City')
        ->assertNoJavaScriptErrors();
});

/**
 * An uploaded File carries its data on the prototype, not as own keys, so
 * the progress calculator used to read it as empty and keep reporting
 * "Resume File" as missing even with a file attached.
 */
it('drops resume from the missing list once a file is attached', function () {
    $career = Career::factory()->create();

    $resume = tempnam(sys_get_temp_dir(), 'resume').'.pdf';
    file_put_contents($resume, '%PDF-1.4 test resume');

    // The upload lives inside the Personal Information section, which is
    // collapsed on load — its input is not in the DOM until expanded.
    $page = visit("/careers/apply/{$career->id}")
        ->click('Decline')
        ->click('#personal-info-trigger');

    $page->assertSee('Resume File')
        ->attach('#resume-file', $resume)
        ->assertDontSee('Resume File')
        ->assertNoJavaScriptErrors();

    unlink($resume);
});
