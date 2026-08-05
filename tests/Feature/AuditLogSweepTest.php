<?php

use App\Models\Application;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use App\Models\Service;
use App\Models\SystemLog;
use App\Models\TeamMember;

test('logging in writes an Authentication system log', function () {
    $admin = adminUser();

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    $log = SystemLog::where('action', 'User logged in')->first();
    expect($log)->not->toBeNull();
    expect($log->details['module'])->toBe('Authentication');
    expect($log->details['status'])->toBe('success');
});

test('updating a client writes a Clients system log', function () {
    $client = Client::factory()->create();

    $this->actingAs(adminUser())->put("/admin/clients/{$client->id}", [
        'status' => 'active',
    ])->assertSessionHasNoErrors();

    $log = SystemLog::where('action', 'Updated client')->first();
    expect($log)->not->toBeNull();
    expect($log->details['module'])->toBe('Clients');
});

test('toggling a service visibility writes a Services system log', function () {
    $service = Service::factory()->create(['is_active' => true]);

    $this->actingAs(adminUser())->patch("/admin/administrator/services/{$service->id}", [
        'is_active' => false,
    ])->assertSessionHasNoErrors();

    $log = SystemLog::where('action', 'Updated service visibility')->first();
    expect($log)->not->toBeNull();
    expect($log->details['module'])->toBe('Services');
});

test('marking an invoice paid writes an Invoices system log', function () {
    $invoice = Invoice::factory()->create(['status' => 'sent']);

    $this->actingAs(adminUser())->post("/admin/invoices/{$invoice->id}/mark-paid")
        ->assertSessionHasNoErrors();

    $log = SystemLog::where('action', 'Marked invoice paid')->first();
    expect($log)->not->toBeNull();
    expect($log->details['module'])->toBe('Invoices');
});

test('updating a team member writes a Users system log', function () {
    $teamMember = TeamMember::factory()->create(['position' => 'Occupational Therapist']);

    $this->actingAs(adminUser())->put("/admin/team/{$teamMember->id}", [
        'email' => $teamMember->user->email,
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'phone' => '555-0100',
        'street_address' => '123 Main St',
        'city' => 'Calgary',
        'province' => 'Alberta',
        'zip_code' => 'T2P 1J9',
        'resident_status' => 'Citizen',
        'position' => 'Occupational Therapist',
        'employment_status' => 'active',
        'hire_date' => now()->toDateString(),
        'hourly_rate' => 60,
        'maximum_caseload' => 15,
        'availability' => collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])
            ->map(fn (string $day) => ['week_day' => $day, 'time_from' => null, 'time_to' => null])
            ->all(),
        'emergency_contact_name' => 'Sam Rivera',
        'emergency_contact_phone' => '555-0199',
    ])->assertSessionHasNoErrors();

    $log = SystemLog::where('action', 'Updated team member')->first();
    expect($log)->not->toBeNull();
    expect($log->details['module'])->toBe('Users');
});

test('creating an intake writes an Intake system log', function () {
    $this->actingAs(adminUser())->post('/admin/intake', validAdminIntakePayload())
        ->assertSessionHasNoErrors();

    $log = SystemLog::where('action', 'Created intake')->first();
    expect($log)->not->toBeNull();
    expect($log->details['module'])->toBe('Intake');
});

test('rating an application writes an Applications system log', function () {
    $application = Application::factory()->create();

    $this->actingAs(adminUser())->patch("/admin/applications/{$application->id}/rating", [
        'candidate_rating' => 4,
    ])->assertSessionHasNoErrors();

    $log = SystemLog::where('action', 'Updated application rating')->first();
    expect($log)->not->toBeNull();
    expect($log->details['module'])->toBe('Applications');
});

test('cancelling a session writes a System module system log', function () {
    $session = ScheduleSession::factory()->create(['status' => 'scheduled']);

    $this->actingAs(adminUser())->post("/admin/sessions/{$session->id}/cancel", [
        'cancel_reason' => 'Client unavailable',
    ])->assertSessionHasNoErrors();

    $log = SystemLog::where('action', 'Cancelled session')->first();
    expect($log)->not->toBeNull();
    expect($log->details['module'])->toBe('System');
    expect($log->details['status'])->toBe('warning');
});
