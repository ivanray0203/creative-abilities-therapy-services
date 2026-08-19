<?php

use App\Models\Intake;

/**
 * All three FSCD variants used to render an identical "FSCD" badge, so an
 * admin could not tell a Specialized Services intake from a BDS or
 * Counselling one on the detail page.
 */
it('names the fscd variant on an ss-fscd intake', function () {
    $intake = Intake::factory()->create([
        'funding_source' => 'SS-FSCD',
        'funding_source_info' => [
            'FSCD_case_worker_name' => 'Dana Fitz',
            'FSCD_case_worker_email' => 'dana@example.com',
            'FSCD_approval_start_date' => '2026-01-05',
        ],
    ]);

    $this->actingAs(adminUser());

    visit("/admin/intake/{$intake->id}")
        ->assertSee('Specialized Services (SS) - FSCD')
        // The badge spells the programme out rather than abbreviating it.
        ->assertDontSee('FSCD (SS)')
        ->assertNoJavaScriptErrors();
});

it('offers the same funding-source wording on the public form as the admin view', function () {
    visit('/intake/apply')
        ->click('Decline')
        ->click('Funding Source')
        ->click('Select a funding')
        ->assertSee('Specialized Services (SS) - FSCD')
        ->assertDontSee('Specialized Serices')
        ->assertSee('Behavioural/Developmental Support (BDS) - FSCD');
});

it('distinguishes a bds-fscd intake from a specialized services one', function () {
    $intake = Intake::factory()->create([
        'funding_source' => 'BDS-FSCD',
        'funding_source_info' => [
            'FSCD_case_worker_name' => 'Dana Fitz',
            'FSCD_case_worker_email' => 'dana@example.com',
            'FSCD_approval_start_date' => '2026-01-05',
        ],
    ]);

    $this->actingAs(adminUser());

    visit("/admin/intake/{$intake->id}")
        ->assertSee('Behavioural/Developmental Support (BDS) - FSCD')
        ->assertDontSee('Specialized Services');
});
