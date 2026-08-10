<?php

namespace App\Models;

use Database\Factories\ClientServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $start_date
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
     * Availed services still waiting on a session — either nothing has ever
     * been booked against them, or every session that was booked has since
     * been cancelled or missed, which frees the service up again (the same
     * rule StoreSessionRequest uses when checking slot conflicts).
     *
     * A service whose session has been held, completed or is merely awaiting
     * sign-off is *not* awaiting scheduling, so it drops out of the picker.
     *
     * @param  Builder<ClientService>  $query
     */
    #[Scope]
    protected function awaitingSchedule(Builder $query): void
    {
        $query->whereDoesntHave(
            'sessions',
            fn (Builder $sessions) => $sessions->whereNotIn('status', ['cancelled', 'no_show']),
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

    /** @return BelongsToMany<ScheduleSession, $this> */
    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(ScheduleSession::class)->withTimestamps();
    }
}
