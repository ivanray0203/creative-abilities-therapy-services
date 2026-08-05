<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    /** @use HasFactory<\Database\Factories\ClientServiceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'no_sessions' => 'integer',
        ];
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

    /** @return HasMany<ScheduleSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ScheduleSession::class, 'linked_client_service_id');
    }
}
