<?php

use App\Models\ServiceOffering;

test('the services list is paginated and scoped to the selected type', function () {
    ServiceOffering::factory()->count(20)->create(['type' => 'general_service']);
    ServiceOffering::factory()->count(2)->create(['type' => 'specific_service']);

    $response = $this->actingAs(adminUser())->get('/admin/services');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/services/index')
        ->has('services.data', 15)
        ->where('services.total', 20)
        ->where('services.current_page', 1)
        ->where('stats.general_service', 20)
        ->where('stats.specific_service', 2)
        ->where('filters.type', 'general_service')
    );

    $secondPage = $this->actingAs(adminUser())->get('/admin/services?type=general_service&page=2');
    $secondPage->assertInertia(fn ($page) => $page->has('services.data', 5));

    $specificResponse = $this->actingAs(adminUser())->get('/admin/services?type=specific_service');
    $specificResponse->assertInertia(fn ($page) => $page->has('services.data', 2));
});
