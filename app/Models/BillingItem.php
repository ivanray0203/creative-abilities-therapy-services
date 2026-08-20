<?php

namespace App\Models;

use Database\Factories\BillingItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One billed service line raised against a client. Lines stay loose until
 * the billing month closes, at which point the generated invoice claims
 * them by filling `invoice_id`.
 *
 * @property-read Client|null $client
 * @property-read User|null $therapist
 * @property-read Invoice|null $invoice
 */
#[Fillable([
    'billing_number', 'therapist_id', 'client_id', 'session_id', 'invoice_service_id',
    'service_name', 'quantity', 'rate', 'amount', 'issued_by_id', 'invoice_id', 'notes',
])]
class BillingItem extends Model
{
    /** @use HasFactory<BillingItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Lines no invoice has claimed yet — what a month-end roll-up bills for.
     *
     * @param  Builder<BillingItem>  $query
     */
    #[Scope]
    protected function unbilled(Builder $query): void
    {
        $query->whereNull('invoice_id');
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

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    /** @return BelongsTo<ScheduleSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ScheduleSession::class, 'session_id');
    }

    /** @return BelongsTo<InvoiceService, $this> */
    public function invoiceService(): BelongsTo
    {
        return $this->belongsTo(InvoiceService::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
