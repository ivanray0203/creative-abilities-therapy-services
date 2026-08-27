<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single item of clinic spending. `amount` is the pre-tax subtotal and
 * `tax_amount` the GST, so reporting can show both a net and a gross figure.
 */
#[Fillable([
    'reference_number', 'expense_date', 'category', 'payee', 'description',
    'amount', 'tax_amount', 'payment_method', 'status', 'recorded_by',
])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * Spending categories, fixed so the reporting groups stay stable.
     *
     * @var array<int, string>
     */
    public const CATEGORIES = [
        'Rent & Facilities',
        'Utilities',
        'Office Supplies',
        'Therapy Supplies & Equipment',
        'Software & Subscriptions',
        'Travel & Mileage',
        'Marketing & Advertising',
        'Professional Fees',
        'Insurance',
        'Training & Development',
        'Contractor Payments',
        'Bank & Payment Fees',
        'Other',
    ];

    /** @var array<int, string> */
    public const PAYMENT_METHODS = [
        'Credit Card',
        'Debit Card',
        'E-Transfer',
        'Cheque',
        'Cash',
        'Pre-Authorized Debit',
        'Other',
    ];

    /** @var array<int, string> */
    public const STATUSES = ['paid', 'pending'];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    /** Pre-tax amount plus GST — what actually left the bank account. */
    protected function total(): Attribute
    {
        return Attribute::get(
            fn (): string => number_format((float) $this->amount + (float) $this->tax_amount, 2, '.', ''),
        );
    }

    /** @var array<int, string> */
    protected $appends = ['total'];

    /**
     * Named `recorder` rather than `recordedBy` on purpose: Laravel snake-cases
     * relation keys when serializing, so `recordedBy` would come out as
     * `recorded_by` and overwrite the foreign key column of that name.
     *
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Expenses falling inside a period. Either bound may be omitted, which
     * leaves that side open.
     *
     * @param  Builder<Expense>  $query
     */
    public function scopeBetween(Builder $query, ?string $from, ?string $to): void
    {
        $query->when($from !== null && $from !== '', fn (Builder $inner) => $inner->whereDate('expense_date', '>=', $from))
            ->when($to !== null && $to !== '', fn (Builder $inner) => $inner->whereDate('expense_date', '<=', $to));
    }
}
