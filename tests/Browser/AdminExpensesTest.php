<?php

use App\Models\Expense;

/**
 * The expense screens end to end: recording one through the form, seeing it
 * on the list, and reading the period report.
 */
it('records an expense through the admin form', function () {
    $this->actingAs(adminUser());

    visit('/admin/expenses/add')
        ->fill('#expense-date', now()->subDay()->toDateString())
        ->click('#expense-category')
        ->click('text=Office Supplies')
        ->fill('#expense-payee', 'Staples Canada')
        ->click('#expense-payment-method')
        ->click('text=Credit Card')
        ->fill('#expense-amount', '120')
        ->fill('#expense-tax', '6')
        ->fill('#expense-description', 'Printer paper and toner.')
        ->click('#expense-submit')
        ->assertPathIs('/admin/expenses')
        ->assertSee('Staples Canada')
        ->assertNoJavaScriptErrors();

    expect(Expense::query()->firstOrFail()->total)->toBe('126.00');
});

it('totals the amount and GST live while typing', function () {
    $this->actingAs(adminUser());

    visit('/admin/expenses/add')
        ->fill('#expense-amount', '200')
        ->fill('#expense-tax', '10')
        ->assertSeeIn('#expense-total', '210.00')
        ->assertNoJavaScriptErrors();
});

it('lists recorded expenses with their totals', function () {
    $this->actingAs(adminUser());

    Expense::factory()->create([
        'payee' => 'Enmax',
        'amount' => '100.00',
        'tax_amount' => '5.00',
        'category' => 'Utilities',
    ]);

    visit('/admin/expenses')
        ->assertSee('Enmax')
        ->assertSee('Utilities')
        ->assertSeeIn('#stat-total', '105.00')
        ->assertSeeIn('#stat-count', '1')
        ->assertNoJavaScriptErrors();
});

it('shows an empty state when nothing matches', function () {
    $this->actingAs(adminUser());

    visit('/admin/expenses')
        ->assertSee('No expenses match these filters.')
        ->assertNoJavaScriptErrors();
});

it('opens the report from the expense list', function () {
    $this->actingAs(adminUser());

    visit('/admin/expenses')
        ->click('#expenses-report-link')
        ->assertPathIs('/admin/expenses/report')
        ->assertSee('Expense Report')
        ->assertNoJavaScriptErrors();
});

it('breaks spending down by category and month', function () {
    $this->actingAs(adminUser());

    Expense::factory()->category('Utilities')->on(now()->startOfYear()->addMonth()->toDateString())
        ->create(['amount' => '100.00', 'tax_amount' => '5.00', 'payee' => 'Enmax']);
    Expense::factory()->category('Insurance')->on(now()->startOfYear()->addMonths(2)->toDateString())
        ->create(['amount' => '50.00', 'tax_amount' => '0.00', 'payee' => 'Intact']);

    visit('/admin/expenses/report')
        ->assertSeeIn('#summary-gross', '155.00')
        ->assertSeeIn('#report-by-category', 'Utilities')
        ->assertSeeIn('#report-by-category', 'Insurance')
        ->assertSeeIn('#report-top-payees', 'Enmax')
        ->assertNoJavaScriptErrors();
});

it('reports zeros for a period with no spending', function () {
    $this->actingAs(adminUser());

    visit('/admin/expenses/report')
        ->assertSeeIn('#summary-gross', '0.00')
        ->assertSeeIn('#summary-count', '0')
        ->assertSee('Nothing in this period.')
        ->assertNoJavaScriptErrors();
});

it('points the CSV export at the current period', function () {
    $this->actingAs(adminUser());

    $href = visit('/admin/expenses/report?from=2026-04-01&to=2026-04-30')->script(
        "document.querySelector('#report-export-link').getAttribute('href')",
    );

    expect($href)->toContain('/admin/expenses/report/export')
        ->toContain('from=2026-04-01')
        ->toContain('to=2026-04-30');
});
