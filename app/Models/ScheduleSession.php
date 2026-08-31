<?php

namespace App\Models;

use Database\Factories\ScheduleSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $scheduled_start
 * @property Carbon|null $scheduled_end
 * @property Carbon|null $start_time
 * @property Carbon|null $end_time
 * @property-read Client|null $client
 * @property-read User|null $therapist
 * @property-read ServiceOffering|null $service
 * @property-read Collection<int, ClientService> $clientServices
 */
#[Fillable([
    'client_id', 'therapist_id', 'service_id', 'service_name',
    'scheduled_start', 'scheduled_end', 'location', 'duration', 'notes', 'status', 'elapsed_time',
    'start_time', 'end_time', 'cancel_reason', 'dispute_reason',
])]
class ScheduleSession extends Model
{
    /** @use HasFactory<ScheduleSessionFactory> */
    use HasFactory;

    /**
     * Statuses that free everything the session was holding: its slot in the
     * conflict check, and its hours in the contract balance. A session in one
     * of these states keeps its rows so the history stays readable, but it
     * stops counting against anything.
     *
     * @var array<int, string>
     */
    public const HOURS_RELEASING_STATUSES = ['cancelled', 'no_show'];

    protected function casts(): array
    {
        return [
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            // Minutes, not free text — see Phase 18's duration migration.
            'duration' => 'integer',
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

    /** @return BelongsTo<ServiceOffering, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class, 'service_id');
    }

    /**
     * The availed services this session delivers. A single visit can cover
     * more than one of the child's services.
     *
     * @return BelongsToMany<ClientService, $this, SessionServiceAllocation>
     */
    public function clientServices(): BelongsToMany
    {
        return $this->belongsToMany(ClientService::class)
            // Phase 20 — the pivot is also the hours ledger: what this
            // session drew for that availed service, and from which contract.
            ->using(SessionServiceAllocation::class)
            ->withPivot(['hours', 'service_contract_id'])
            ->withTimestamps();
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'session_id');
    }

    /** @return HasMany<Complaint, $this> */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'session_id');
    }
}
