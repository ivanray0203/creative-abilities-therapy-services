<?php

namespace App\Models;

use Database\Factories\ServiceOfferingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, mixed>|null $metadata
 */
#[Fillable(['name', 'code', 'description', 'is_active', 'type', 'base_price', 'metadata'])]
class ServiceOffering extends Model
{
    /** @use HasFactory<ServiceOfferingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'base_price' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /** @return HasMany<ClientService, $this> */
    public function clientServices(): HasMany
    {
        return $this->hasMany(ClientService::class, 'service_id');
    }

    /** @return HasMany<ScheduleSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ScheduleSession::class, 'service_id');
    }
}
