<?php

use App\Models\Service;
use App\Models\SystemLog;
use App\Models\User;

test('the administrator index shows filtered logs, stats, admin users, and services', function () {
    SystemLog::factory()->create(['action' => 'logged_in', 'details' => ['status' => 'success', 'module' => 'Authentication']]);
    SystemLog::factory()->create(['action' => 'deleted_document', 'details' => ['status' => 'error', 'module' => 'Clients']]);
    Service::factory()->create();
    $otherAdmin = adminUser();

    $response = $this->actingAs(adminUser())->get('/admin/administrator');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/administrator/index')
        ->has('logs.data', 2)
        ->where('logStats.success', 1)
        ->where('logStats.error', 1)
        ->has('services', 1)
    );

    expect($otherAdmin)->not->toBeNull();

    $filtered = $this->actingAs(adminUser())->get('/admin/administrator?module=Clients');
    $filtered->assertInertia(fn ($page) => $page->has('logs.data', 1));
});

test('an admin can create, update, and deactivate an admin user', function () {
    $this->actingAs(adminUser())->post('/admin/administrator/users', [
        'first_name' => 'Jamie',
        'last_name' => 'Lee',
        'email' => 'jamie.lee@example.com',
        'phone' => '555-0100',
    ])->assertSessionHasNoErrors();

    $created = User::where('email', 'jamie.lee@example.com')->first();
    expect($created)->not->toBeNull();
    expect($created->role)->toBe('admin');
    expect($created->is_active)->toBeTrue();

    $this->actingAs(adminUser())->patch("/admin/users/{$created->id}/admin-update", [
        'first_name' => 'Jamie',
        'last_name' => 'Lee-Smith',
    ])->assertSessionHasNoErrors();

    expect($created->refresh()->last_name)->toBe('Lee-Smith');

    $this->actingAs(adminUser())->patch("/admin/users/{$created->id}/admin-update", [
        'is_active' => false,
    ])->assertSessionHasNoErrors();

    expect($created->refresh()->is_active)->toBeFalse();
});

test('toggling a service visibility is reflected on the public services page', function () {
    $service = Service::factory()->create(['is_active' => true]);

    $this->actingAs(adminUser())->patch("/admin/administrator/services/{$service->id}", [
        'is_active' => false,
    ])->assertSessionHasNoErrors();

    expect($service->refresh()->is_active)->toBeFalse();

    $publicResponse = $this->get('/services');
    $publicResponse->assertInertia(fn ($page) => $page
        ->component('public/services')
        ->where('services', fn ($services) => collect($services)->doesntContain(fn ($item) => $item['id'] === $service->id))
    );
});

test('an admin can update their own profile and notification preferences', function () {
    $admin = adminUser();

    $this->actingAs($admin)->put('/admin/administrator/profile', [
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'phone' => '555-0199',
        'new_intake' => true,
        'invoice_payments' => false,
        'session_reminders' => true,
        'new_applications' => false,
    ])->assertSessionHasNoErrors();

    $admin->refresh();
    expect($admin->first_name)->toBe('Updated');
    expect($admin->new_intake)->toBeTrue();
    expect($admin->invoice_payments)->toBeFalse();
});
