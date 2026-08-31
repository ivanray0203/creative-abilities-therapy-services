<?php

use App\Models\Expense;
use App\Models\SystemLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validExpensePayload(array $overrides = []): array
{
    return array_merge([
        'expense_date' => now()->subDay()->toDateString(),
        'category' => 'Office Supplies',
        'payee' => 'Staples Canada',
        'description' => 'Printer paper and toner.',
        'amount' => '120.00',
        'tax_amount' => '6.00',
        'payment_method' => 'Credit Card',
        'status' => 'paid',
    ], $overrides);
}

test('a non-admin is redirected away from the expense routes', function () {
    $this->actingAs(therapistUser())->get('/admin/expenses')->assertRedirect('/therapist');
    $this->actingAs(therapistUser())->get('/admin/expenses/report')->assertRedirect('/therapist');
});

test('the expense list shows totals and rows', function () {
    Expense::factory()->create(['amount' => '100.00', 'tax_amount' => '5.00']);
    Expense::factory()->pending()->create(['amount' => '40.00', 'tax_amount' => '2.00']);

    $response = $this->actingAs(adminUser())->get('/admin/expenses');

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('admin/expenses/index')
        ->where('stats.count', 2)
        ->where('stats.total', 147)
        ->where('stats.pending_total', 40)
        ->has('expenses.data', 2)
        ->has('categories')
    );
});

test('an admin can record an expense and it gets a reference number', function () {
    $admin = adminUser();

    $response = $this->actingAs($admin)->post('/admin/expenses', validExpensePayload());

    $response->assertRedirect('/admin/expenses')->assertSessionHasNoErrors();

    $expense = Expense::query()->firstOrFail();

    expect($expense->reference_number)->toBe('EXP-'.now()->year.'-001')
        ->and($expense->payee)->toBe('Staples Canada')
        ->and($expense->recorded_by)->toBe($admin->id)
        // Appended accessor, not a column.
        ->and($expense->total)->toBe('126.00');
});

test('recording an expense is written to the audit log', function () {
    $this->actingAs(adminUser())->post('/admin/expenses', validExpensePayload());

    expect(SystemLog::query()->where('action', 'Recorded expense')->exists())->toBeTrue();
});

test('an expense without GST totals to its amount', function () {
    $this->actingAs(adminUser())->post('/admin/expenses', validExpensePayload([
        'tax_amount' => null,
    ]))->assertSessionHasNoErrors();

    expect(Expense::query()->firstOrFail()->total)->toBe('120.00');
});

test('recording an expense requires the core fields', function () {
    $response = $this->actingAs(adminUser())->post('/admin/expenses', []);

    $response->assertSessionHasErrors([
        'expense_date', 'category', 'payee', 'amount', 'payment_method', 'status',
    ]);

    expect(Expense::count())->toBe(0);
});

test('an expense cannot be dated in the future', function () {
    $response = $this->actingAs(adminUser())->post('/admin/expenses', validExpensePayload([
        'expense_date' => now()->addWeek()->toDateString(),
    ]));

    $response->assertSessionHasErrors('expense_date');
    expect(Expense::count())->toBe(0);
});

test('an expense category must be one of the known categories', function () {
    $response = $this->actingAs(adminUser())->post('/admin/expenses', validExpensePayload([
        'category' => 'Yacht Maintenance',
    ]));

    $response->assertSessionHasErrors('category');
});

test('an admin can update an expense', function () {
    $expense = Expense::factory()->create(['payee' => 'Old Vendor']);

    $this->actingAs(adminUser())
        ->put("/admin/expenses/{$expense->id}", validExpensePayload(['payee' => 'New Vendor']))
        ->assertRedirect('/admin/expenses')
        ->assertSessionHasNoErrors();

    expect($expense->refresh()->payee)->toBe('New Vendor');
});

test('an admin can remove an expense', function () {
    $expense = Expense::factory()->create();

    $this->actingAs(adminUser())
        ->delete("/admin/expenses/{$expense->id}")
        ->assertRedirect('/admin/expenses');

    expect(Expense::count())->toBe(0);
});

test('the list filters by category, status, and date range', function () {
    Expense::factory()->category('Utilities')->on('2026-03-10')->create();
    Expense::factory()->category('Insurance')->on('2026-07-10')->create();
    Expense::factory()->category('Utilities')->pending()->on('2026-07-20')->create();

    $admin = adminUser();

    $this->actingAs($admin)->get('/admin/expenses?category=Utilities')
        ->assertInertia(fn ($page) => $page->where('stats.count', 2));

    $this->actingAs($admin)->get('/admin/expenses?status=pending')
        ->assertInertia(fn ($page) => $page->where('stats.count', 1));

    $this->actingAs($admin)->get('/admin/expenses?from=2026-07-01&to=2026-07-31')
        ->assertInertia(fn ($page) => $page->where('stats.count', 2));
});

test('the list searches payee, reference, and description', function () {
    Expense::factory()->create(['payee' => 'Telus Communications']);
    Expense::factory()->create(['payee' => 'Staples', 'description' => 'Laminator refill']);

    $admin = adminUser();

    $this->actingAs($admin)->get('/admin/expenses?search=Telus')
        ->assertInertia(fn ($page) => $page->where('stats.count', 1));

    $this->actingAs($admin)->get('/admin/expenses?search=laminator')
        ->assertInertia(fn ($page) => $page->where('stats.count', 1));
});
