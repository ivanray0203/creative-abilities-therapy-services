<?php

it('loads the intake application page without javascript errors', function () {
    $page = visit('/intake/apply');

    $page->assertSee('Intake Form')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
});

it('keeps the preview button enabled before the form is complete', function () {
    $page = visit('/intake/apply');

    $page->click('Decline')
        ->assertButtonEnabled('Preview & Submit Intake Form');
});

it('lists the missing fields and consents instead of opening the preview', function () {
    $page = visit('/intake/apply');

    $page->click('Decline')
        ->click('Preview & Submit Intake Form')
        ->assertSee('Incomplete Application')
        ->assertSee('Child First Name')
        ->assertSee('Terms And Conditions Consent')
        ->assertSee('Privacy Policy Consent')
        ->assertDontSee('Confirm and Submit');
});

it('no longer lists a consent once it is checked', function () {
    $page = visit('/intake/apply');

    $page->click('Decline')
        ->click('#consentTerms')
        ->click('#consentPrivacy')
        ->click('Preview & Submit Intake Form')
        ->assertSee('Incomplete Application')
        ->assertDontSee('Terms And Conditions Consent')
        ->assertDontSee('Privacy Policy Consent');
});
