/**
 * Money formatting shared by the expense screens. Amounts arrive as
 * decimal strings from Eloquent casts, or as numbers from the report
 * aggregates, so both are accepted.
 */
export function formatMoney(value: string | number): string {
    const amount = typeof value === 'number' ? value : Number(value);

    return new Intl.NumberFormat('en-CA', {
        style: 'currency',
        currency: 'CAD',
    }).format(Number.isFinite(amount) ? amount : 0);
}

/** `2026-08` → `Aug 2026`, for the month-by-month report rows. */
export function formatMonth(label: string): string {
    const [year, month] = label.split('-');
    const date = new Date(Number(year), Number(month) - 1, 1);

    return Number.isNaN(date.getTime())
        ? label
        : date.toLocaleDateString('en-CA', { month: 'short', year: 'numeric' });
}
