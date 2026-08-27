/**
 * Admin-facing Expense model shape, matching the `Expense` Eloquent model
 * (app/Models/Expense.php). `total` is an appended accessor, not a column.
 */
export interface Expense {
    id: number;
    reference_number: string;
    expense_date: string;
    category: string;
    payee: string;
    description: string | null;
    amount: string;
    tax_amount: string;
    total: string;
    payment_method: string;
    status: 'paid' | 'pending';
    recorded_by: number | null;
    recorder?: { first_name: string; last_name: string } | null;
}

/** One row of a report breakdown — by category, month, method, or payee. */
export interface ExpenseBreakdownRow {
    label: string;
    total: number;
    count: number;
}

export interface ExpenseSummary {
    net: number;
    tax: number;
    gross: number;
    count: number;
    average: number;
    pending: number;
}

export interface ExpensePeriod {
    from: string;
    to: string;
    category: string;
    status: string;
}
