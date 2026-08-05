<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Artisan;

test('the overdue command transitions past-due sent invoices and appends a timeline entry', function () {
    $overdue = Invoice::factory()->create([
        'status' => 'sent',
        'due_date' => now()->subDays(5)->toDateString(),
        'timeline' => [],
    ]);
    $notYetDue = Invoice::factory()->create([
        'status' => 'sent',
        'due_date' => now()->addDays(5)->toDateString(),
    ]);
    $alreadyPaid = Invoice::factory()->create([
        'status' => 'paid',
        'due_date' => now()->subDays(5)->toDateString(),
    ]);

    Artisan::call('invoices:mark-overdue');

    expect($overdue->refresh()->status)->toBe('overdue');
    expect($overdue->timeline)->toHaveCount(1);
    expect($overdue->timeline[0]['title'])->toBe('Invoice marked overdue');

    expect($notYetDue->refresh()->status)->toBe('sent');
    expect($alreadyPaid->refresh()->status)->toBe('paid');
});
