<?php

it('loads the intake application page without javascript errors', function () {
    $page = visit('/intake/apply');

    $page->assertSee('Intake Form')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
});

it('keeps the submit button disabled until the required consents are accepted', function () {
    $page = visit('/intake/apply');

    $page->click('Decline')
        ->assertButtonDisabled('Preview & Submit Intake Form');
});

it('enables the submit button once both required consents are checked', function () {
    $page = visit('/intake/apply');

    $page->click('Decline')
        ->click('#consentTerms')
        ->click('#consentPrivacy')
        ->assertChecked('#consentTerms')
        ->assertChecked('#consentPrivacy')
        ->assertButtonEnabled('Preview & Submit Intake Form');
});
