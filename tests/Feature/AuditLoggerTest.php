<?php

use App\Models\SystemLog;
use App\Services\AuditLogger;

test('logging as an authenticated user records the user id, email, and ip', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    AuditLogger::log('Created client', 'Clients', 'Created client #1');

    $log = SystemLog::first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($admin->id);
    expect($log->action)->toBe('Created client');
    expect($log->details['status'])->toBe('success');
    expect($log->details['user_email'])->toBe($admin->email);
    expect($log->details['module'])->toBe('Clients');
    expect($log->details['detail'])->toBe('Created client #1');
});

test('logging without an authenticated user falls back to a system email', function () {
    AuditLogger::log('Intake submitted', 'Intake', 'New public intake submission', 'info');

    $log = SystemLog::first();
    expect($log->user_id)->toBeNull();
    expect($log->details['user_email'])->toBe('system');
    expect($log->details['status'])->toBe('info');
});
