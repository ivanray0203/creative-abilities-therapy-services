<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<int, array<string, mixed>>|null $services Line items shaped like {name, description, period, numberOfSessions, rate, rate_numeric}.
 * @property array<int, array<string, mixed>>|null $timeline
 * @property-read Client|null $client
 * @property-read User|null $therapist
 */
#[Fillable([
    'client_id', 'therapist_id', 'billing_account_id', 'session_id', 'reference', 'invoice_id',
    'services', 'sub_total', 'tax_percentage', 'gst', 'total', 'amount_due', 'invoice_date',
    'due_date', 'paid_at', 'paid_date', 'status', 'processed_by', 'issued_by_id', 'notes',
    'timeline', 'bill_to_name', 'bill_to_email', 'bill_to_phone', 'bill_to_address', 'billed_by',
    'linked_therapist_invoice_id',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('orderByInvoiceDate', function ($query) {
            $query->orderByDesc('invoice_date')->orderByDesc('created_at');
        });
    }

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'sub_total' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'gst' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'paid_date' => 'date',
            'timeline' => 'array',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<User, $this> */
    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    /** @return BelongsTo<BillingAccount, $this> */
    public function billingAccount(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class);
    }

    /** @return BelongsTo<ScheduleSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ScheduleSession::class, 'session_id');
    }

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    /** @return BelongsTo<Invoice, $this> */
    public function linkedTherapistInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'linked_therapist_invoice_id');
    }

    /** @return HasMany<Invoice, $this> */
    public function linkedAdminInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'linked_therapist_invoice_id');
    }

    public function calculateTotals(): void
    {
        $subTotal = collect($this->services ?? [])->sum(fn (array $line) => (float) ($line['rate_numeric'] ?? 0) * (int) ($line['numberOfSessions'] ?? 1));

        $gst = round($subTotal * ((float) $this->tax_percentage / 100), 2);

        $this->sub_total = $subTotal;
        $this->gst = $gst;
        $this->total = $subTotal + $gst;
        $this->amount_due = $this->total;
    }
}
