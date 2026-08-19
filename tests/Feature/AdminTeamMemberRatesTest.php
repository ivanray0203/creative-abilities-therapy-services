<?php

use App\Models\InvoiceService;
use App\Models\TeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the team member page carries the rate card and this member\'s overrides', function () {
    $homeVisit = InvoiceService::factory()->code('occupational-therapist-home-visit')->create();
    $retired = InvoiceService::factory()->code('ot-team-meeting')->create(['is_active' => false]);
    $teamMember = TeamMember::factory()->create(['user_id' => therapistUser()->id]);

    $teamMember->invoiceServiceRates()->attach($homeVisit->id, ['rate_private' => '175.00']);

    $this->actingAs(adminUser())->get("/admin/team/{$teamMember->id}")
        ->assertInertia(fn ($page) => $page
            ->component('admin/team/show')
            ->where('invoiceServices.0.code', 'occupational-therapist-home-visit')
            ->where('invoiceServices.0.rate_private', '142.14')
            ->where("invoiceServiceRates.{$homeVisit->id}.rate_private", '175.00')
            ->where("invoiceServiceRates.{$homeVisit->id}.rate_fscd", null)
            ->missing("invoiceServiceRates.{$retired->id}")
        );

    expect(collect($this->get("/admin/team/{$teamMember->id}")->viewData('page')['props']['invoiceServices'])
        ->pluck('id')->all())->not->toContain($retired->id);
});

test('an admin can set a team member\'s rate for an invoice service', function () {
    $homeVisit = InvoiceService::factory()->code('physiotherapist-home-visit')->create();
    $travel = InvoiceService::factory()->code('physiotherapist-travel')->create();
    $teamMember = TeamMember::factory()->create(['user_id' => therapistUser()->id]);

    $this->actingAs(adminUser())
        ->patch("/admin/team/{$teamMember->id}/rates", [
            'rates' => [
                ['invoice_service_id' => $homeVisit->id, 'rate_fscd' => '90.00', 'rate_private' => '160.00'],
                ['invoice_service_id' => $travel->id, 'rate_fscd' => null, 'rate_private' => null],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseCount('team_member_invoice_service_rates', 1);
    $this->assertDatabaseHas('team_member_invoice_service_rates', [
        'team_member_id' => $teamMember->id,
        'invoice_service_id' => $homeVisit->id,
        'rate_fscd' => '90.00',
        'rate_private' => '160.00',
    ]);
});

test('a rate left blank falls back to the published rate instead of storing a zero', function () {
    $homeVisit = InvoiceService::factory()->code('behavioural-consultant-home-visit')->create();
    $teamMember = TeamMember::factory()->create(['user_id' => therapistUser()->id]);

    $this->actingAs(adminUser())
        ->patch("/admin/team/{$teamMember->id}/rates", [
            'rates' => [
                ['invoice_service_id' => $homeVisit->id, 'rate_fscd' => null, 'rate_private' => '200.00'],
            ],
        ]);

    $teamMember->load('invoiceServiceRates');

    expect($teamMember->rateFor($homeVisit, 'private'))->toBe('200.00');
    expect($teamMember->rateFor($homeVisit, 'fscd'))->toBe('77.25');
});

test('a team member with no override bills at the published rate', function () {
    $homeVisit = InvoiceService::factory()->code('psychologist-home-visit')->create();
    $teamMember = TeamMember::factory()->create(['user_id' => therapistUser()->id]);
    $teamMember->load('invoiceServiceRates');

    expect($teamMember->rateFor($homeVisit, 'fscd'))->toBe('98.37');
    expect($teamMember->rateFor($homeVisit, 'private'))->toBe('98.37');
});

test('clearing both rates removes the override row', function () {
    $homeVisit = InvoiceService::factory()->code('speech-language-pathologist-home-visit')->create();
    $teamMember = TeamMember::factory()->create(['user_id' => therapistUser()->id]);
    $teamMember->invoiceServiceRates()->attach($homeVisit->id, ['rate_fscd' => '120.00']);

    $this->actingAs(adminUser())
        ->patch("/admin/team/{$teamMember->id}/rates", [
            'rates' => [
                ['invoice_service_id' => $homeVisit->id, 'rate_fscd' => null, 'rate_private' => null],
            ],
        ]);

    $this->assertDatabaseCount('team_member_invoice_service_rates', 0);
});

test('rates are rejected when negative or pointing at a service that does not exist', function () {
    $homeVisit = InvoiceService::factory()->code('slp-documentation')->create();
    $teamMember = TeamMember::factory()->create(['user_id' => therapistUser()->id]);

    $this->actingAs(adminUser())
        ->patch("/admin/team/{$teamMember->id}/rates", [
            'rates' => [['invoice_service_id' => $homeVisit->id, 'rate_fscd' => -5]],
        ])
        ->assertSessionHasErrors('rates.0.rate_fscd');

    $this->actingAs(adminUser())
        ->patch("/admin/team/{$teamMember->id}/rates", [
            'rates' => [['invoice_service_id' => 99999, 'rate_fscd' => '10.00']],
        ])
        ->assertSessionHasErrors('rates.0.invoice_service_id');

    $this->assertDatabaseCount('team_member_invoice_service_rates', 0);
});

test('a therapist cannot set team member rates', function () {
    $teamMember = TeamMember::factory()->create(['user_id' => therapistUser()->id]);
    $homeVisit = InvoiceService::factory()->code('ot-documentation')->create();

    // The role middleware bounces non-admins to their own home rather than
    // answering 403 — same as every other admin route.
    $this->actingAs(therapistUser())
        ->patch("/admin/team/{$teamMember->id}/rates", [
            'rates' => [['invoice_service_id' => $homeVisit->id, 'rate_fscd' => '10.00']],
        ])
        ->assertRedirect('/therapist');

    $this->assertDatabaseCount('team_member_invoice_service_rates', 0);
});
