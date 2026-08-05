<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property-read ClientService|null $linkedClientService
 */
#[Fillable([
    'client_id', 'therapist_id', 'service_id', 'service_name', 'linked_client_service_id',
    'scheduled_start', 'scheduled_end', 'location', 'duration', 'notes', 'status', 'elapsed_time',
    'start_time', 'end_time', 'cancel_reason', 'dispute_reason',
])]
class ScheduleSession extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduleSessionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
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

    /** @return BelongsTo<ClientService, $this> */
    public function linkedClientService(): BelongsTo
    {
        return $this->belongsTo(ClientService::class, 'linked_client_service_id');
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
