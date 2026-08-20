<?php

use App\Models\InvoiceService;
use App\Models\ServiceOffering;

test('the services list is the invoice rate card, paginated in sheet order', function () {
    InvoiceService::factory()->count(20)->create();

    $response = $this->actingAs(adminUser())->get('/admin/services');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/services/index')
        ->has('services.data', 15)
        ->where('services.total', 20)
        ->where('services.current_page', 1)
        ->where('filters.search', '')
        ->where('filters.discipline', 'all')
    );

    $secondPage = $this->actingAs(adminUser())->get('/admin/services?page=2');
    $secondPage->assertInertia(fn ($page) => $page->has('services.data', 5));
});

test('the services list no longer shows the service offering catalog', function () {
    ServiceOffering::factory()->count(6)->create(['type' => 'general_service']);
    InvoiceService::factory()->count(2)->create();

    $this->actingAs(adminUser())->get('/admin/services')
        ->assertInertia(fn ($page) => $page
            ->where('services.total', 2)
            ->missing('stats')
            ->missing('filters.type')
        );
});
