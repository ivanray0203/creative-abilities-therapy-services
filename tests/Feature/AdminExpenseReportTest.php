<?php

use App\Models\Expense;
use App\Models\SystemLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Three expenses spread across two months and two categories, so every
 * breakdown has something to group.
 */
function seedReportableExpenses(): void
{
    Expense::factory()->category('Utilities')->on('2026-03-10')
        ->create(['amount' => '100.00', 'tax_amount' => '5.00', 'payee' => 'Enmax', 'payment_method' => 'Credit Card']);

    Expense::factory()->category('Utilities')->on('2026-04-15')
        ->create(['amount' => '200.00', 'tax_amount' => '10.00', 'payee' => 'Enmax', 'payment_method' => 'Credit Card']);

    Expense::factory()->category('Insurance')->on('2026-04-20')->pending()
        ->create(['amount' => '50.00', 'tax_amount' => '0.00', 'payee' => 'Intact', 'payment_method' => 'Cheque']);
}

test('the report totals the period', function () {
    seedReportableExpenses();

    $response = $this->actingAs(adminUser())
        ->get('/admin/expenses/report?from=2026-01-01&to=2026-12-31');

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('admin/expenses/report')
        ->where('summary.net', 350)
        ->where('summary.tax', 15)
        ->where('summary.gross', 365)
        ->where('summary.count', 3)
        ->where('summary.pending', 50)
    );
});

test('the report groups by category, month, method, and payee', function () {
    seedReportableExpenses();

    $response = $this->actingAs(adminUser())
        ->get('/admin/expenses/report?from=2026-01-01&to=2026-12-31');

    $response->assertInertia(fn ($page) => $page
        // Ordered by total, so Utilities (315) leads Insurance (50).
        ->where('byCategory.0.label', 'Utilities')
        ->where('byCategory.0.total', 315)
        ->where('byCategory.0.count', 2)
        ->where('byCategory.1.label', 'Insurance')
        // Months come back chronologically, keyed YYYY-MM.
        ->where('byMonth.0.label', '2026-03')
        ->where('byMonth.0.total', 105)
        ->where('byMonth.1.label', '2026-04')
        ->where('byMonth.1.total', 260)
        ->where('byPaymentMethod.0.label', 'Credit Card')
        ->where('topPayees.0.label', 'Enmax')
        ->where('topPayees.0.total', 315)
    );
});

test('the report respects the date range', function () {
    seedReportableExpenses();

    $this->actingAs(adminUser())
        ->get('/admin/expenses/report?from=2026-04-01&to=2026-04-30')
        ->assertInertia(fn ($page) => $page
            ->where('summary.count', 2)
            ->where('summary.gross', 260)
        );
});

test('the report filters by category and status', function () {
    seedReportableExpenses();

    $admin = adminUser();

    $this->actingAs($admin)
        ->get('/admin/expenses/report?from=2026-01-01&to=2026-12-31&category=Insurance')
        ->assertInertia(fn ($page) => $page->where('summary.count', 1));

    $this->actingAs($admin)
        ->get('/admin/expenses/report?from=2026-01-01&to=2026-12-31&status=paid')
        ->assertInertia(fn ($page) => $page->where('summary.count', 2));
});

test('the report defaults to the current year', function () {
    Expense::factory()->on(now()->toDateString())->create(['amount' => '10.00', 'tax_amount' => '0.00']);
    Expense::factory()->on(now()->subYears(2)->toDateString())->create(['amount' => '99.00', 'tax_amount' => '0.00']);

    $this->actingAs(adminUser())->get('/admin/expenses/report')
        ->assertInertia(fn ($page) => $page
            ->where('summary.count', 1)
            ->where('period.from', now()->startOfYear()->toDateString())
        );
});

test('an empty period reports zeros rather than failing', function () {
    $this->actingAs(adminUser())
        ->get('/admin/expenses/report?from=2030-01-01&to=2030-12-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.count', 0)
            ->where('summary.gross', 0)
            // Guards the divide-by-zero in the average.
            ->where('summary.average', 0)
            ->has('byCategory', 0)
        );
});

test('the CSV export streams the filtered rows', function () {
    seedReportableExpenses();

    $response = $this->actingAs(adminUser())
        ->get('/admin/expenses/report/export?from=2026-04-01&to=2026-04-30');

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=utf-8')
        ->assertDownload('expenses-2026-04-01-to-2026-04-30.csv');

    $csv = $response->streamedContent();
    $lines = array_values(array_filter(explode("\n", trim($csv))));

    // Header plus the two April rows; the March one is outside the range.
    expect($lines)->toHaveCount(3);
    expect($lines[0])->toContain('Reference', 'Date', 'Category', 'Total');
    expect($csv)->toContain('Enmax')->toContain('Intact')->not->toContain('2026-03-10');
});

test('the CSV export is written to the audit log', function () {
    seedReportableExpenses();

    $this->actingAs(adminUser())
        ->get('/admin/expenses/report/export?from=2026-01-01&to=2026-12-31')
        ->streamedContent();

    expect(SystemLog::query()->where('action', 'Exported expense report')->exists())->toBeTrue();
});

test('a non-admin cannot export the expense report', function () {
    $this->actingAs(therapistUser())->get('/admin/expenses/report/export')->assertRedirect('/therapist');
});
