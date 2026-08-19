<?php

use App\Models\InvoiceService;
use Database\Seeders\InvoiceServiceSeeder;

test('the services page lists the rate card in sheet order with both rates', function () {
    $this->seed(InvoiceServiceSeeder::class);

    $this->actingAs(adminUser())->get('/admin/services')
        ->assertInertia(fn ($page) => $page
            ->component('admin/services/index')
            ->where('services.total', 147)
            ->has('services.data', 15)
            ->where('services.data.0.name', 'Speech-Language Pathologist Home Visit')
            ->where('services.data.0.rate_fscd', '100.68')
            ->where('services.data.0.rate_private', '100.68')
            ->where('services.data.0.discipline', 'slp')
        );
});

test('the rate card can be searched by name and by code', function () {
    $this->seed(InvoiceServiceSeeder::class);

    $this->actingAs(adminUser())->get('/admin/services?search=Mileage')
        ->assertInertia(fn ($page) => $page
            ->where('services.total', 8)
            ->where('filters.search', 'Mileage')
        );

    $this->actingAs(adminUser())->get('/admin/services?search=psychologist-home-visit')
        ->assertInertia(fn ($page) => $page
            ->where('services.total', 1)
            ->where('services.data.0.code', 'psychologist-home-visit')
        );
});

test('the rate card can be filtered to one discipline', function () {
    $this->seed(InvoiceServiceSeeder::class);

    $this->actingAs(adminUser())->get('/admin/services?discipline=aide')
        ->assertInertia(fn ($page) => $page
            ->where('services.total', 8)
            ->where('filters.discipline', 'aide')
            ->where('services.data.0.discipline', 'aide')
        );
});

test('the disciplines filter is offered from the rate card itself', function () {
    $this->seed(InvoiceServiceSeeder::class);

    $this->actingAs(adminUser())->get('/admin/services')
        ->assertInertia(fn ($page) => $page
            ->where('disciplines', ['aide', 'bc', 'ot', 'other', 'psych', 'pt', 'slp'])
        );
});

test('an admin can edit a rate line published rates', function () {
    $service = InvoiceService::factory()->code('ot-documentation')->create();

    $this->actingAs(adminUser())
        ->patch("/admin/services/{$service->id}/rates", [
            'rate_fscd' => '99.99',
            'rate_private' => '150.50',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $service->refresh();

    expect($service->rate_fscd)->toBe('99.99');
    expect($service->rate_private)->toBe('150.50');
});

test('a blank rate clears the line for that funding stream instead of zeroing it', function () {
    $service = InvoiceService::factory()->code('pt-documentation')->create();

    $this->actingAs(adminUser())
        ->patch("/admin/services/{$service->id}/rates", [
            'rate_fscd' => '80.00',
            'rate_private' => null,
        ]);

    $service->refresh();

    expect($service->rate_fscd)->toBe('80.00');
    expect($service->rate_private)->toBeNull();
    expect($service->rateFor('private'))->toBeNull();
});

test('editing rates never rewrites the line name, code or discipline', function () {
    $service = InvoiceService::factory()->code('bc-team-meeting')->create();

    $this->actingAs(adminUser())
        ->patch("/admin/services/{$service->id}/rates", [
            'rate_fscd' => '10.00',
            'rate_private' => '20.00',
            'name' => 'Renamed',
            'code' => 'renamed',
            'discipline' => 'other',
        ]);

    $service->refresh();

    expect($service->name)->toBe('BC - Team Meeting');
    expect($service->code)->toBe('bc-team-meeting');
    expect($service->discipline)->toBe('bc');
});

test('negative rates are rejected', function () {
    $service = InvoiceService::factory()->code('slp-team-meeting')->create();

    $this->actingAs(adminUser())
        ->patch("/admin/services/{$service->id}/rates", ['rate_fscd' => -1])
        ->assertSessionHasErrors('rate_fscd');

    expect($service->refresh()->rate_fscd)->toBe('100.68');
});

test('a therapist cannot edit the published rate card', function () {
    $service = InvoiceService::factory()->code('psych-documentation')->create();

    // The role middleware bounces non-admins to their own home rather than
    // answering 403 — same as every other admin route.
    $this->actingAs(therapistUser())
        ->patch("/admin/services/{$service->id}/rates", ['rate_fscd' => '1.00'])
        ->assertRedirect('/therapist');

    expect($service->refresh()->rate_fscd)->toBe('98.37');
});
