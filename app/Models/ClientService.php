<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ClientServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $start_date
 * @property-read SessionServiceAllocation|null $pivot Set only when the service was loaded through a session's ledger rows.
 * @property-read Client|null $client
 * @property-read ServiceOffering|null $service
 * @property-read User|null $therapist
 */
#[Fillable(['client_id', 'service_id', 'therapist_id', 'frequency', 'duration', 'start_date', 'funding_source', 'no_sessions', 'goals'])]
class ClientService extends Model
{
    /** @use HasFactory<ClientServiceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'no_sessions' => 'integer',
        ];
    }

    /**
     * Availed services a session dated `$date` may be booked against.
     *
     * Phase 20 replaced `awaitingSchedule()`, which allowed at most one
     * session per availed service for its entire life. Bookability is now the
     * contract's business: admin has to have authorized the work, the session
     * has to fall inside the authorized period, and there have to be hours
     * left in the pool.
     *
     * Note this takes a date. The same availed service is bookable in August
     * and not in September, so every caller has to say which day it means —
     * the pickers mean today, the request validator means the session's own
     * date.
     *
     * @param  Builder<ClientService>  $query
     */
    #[Scope]
    protected function bookableOn(Builder $query, CarbonInterface $date): void
    {
        $query->whereIn(
            'client_services.id',
            ServiceContract::query()->openOn($date)->select('client_service_id'),
        );
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<ServiceOffering, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceOffering::class, 'service_id');
    }

    /** @return BelongsTo<User, $this> */
    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    /**
     * The offering's name, or a neutral label when the offering was deleted
     * out from under the availed service.
     */
    public function serviceName(): string
    {
        return $this->service !== null ? $this->service->name : 'Service';
    }

    /**
     * Phase 21 — the hour-tracking sheet's funding code for this service, from
     * the funder already on record.
     *
     * Its own `funding_source` first, the child's intake second: a child can
     * be approved under one stream and avail a service funded by another, so
     * the service-level record is the more specific of the two.
     *
     * Null when neither is set, or when the funder has no code on the sheet —
     * insurance being the real case. The same mapping the rollout migration
     * used, so a contract issued today lands on the code a backfilled one did.
     */
    public function defaultFundingCode(): ?string
    {
        return ServiceContract::fundingCodeFor($this->funding_source)
            ?? ServiceContract::fundingCodeFor($this->client?->originalIntake?->funding_source);
    }

    /** @return BelongsToMany<ScheduleSession, $this, SessionServiceAllocation> */
    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(ScheduleSession::class)
            // Phase 20 — the pivot carries the hours this session drew for
            // this service, and the contract they came out of.
            ->using(SessionServiceAllocation::class)
            ->withPivot(['hours', 'service_contract_id'])
            ->withTimestamps();
    }

    /**
     * Admin's authorizations for this availed service, newest period first.
     *
     * @return HasMany<ServiceContract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(ServiceContract::class)->orderByDesc('period_start');
    }

    /**
     * The contract a session on `$date` belongs to, or null when the service
     * is not authorized for that day at all.
     *
     * Overlapping periods are rejected when a contract is issued, so at most
     * one can match. An exhausted contract still comes back: a session
     * already drawing from it has to be editable, and "the pool is empty" is
     * a better thing to tell a therapist than "there is no contract".
     */
    public function contractOn(CarbonInterface $date): ?ServiceContract
    {
        return $this->contracts()->coveringOn($date)->first();
    }
}
