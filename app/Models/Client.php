<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property array<int, array<string, mixed>>|null $clinical_notes
 * @property array<int, string>|null $allergies
 * @property array<int, array<string, mixed>>|null $timeline
 * @property array<int, int>|null $consents
 * @property array<int, array<string, mixed>>|null $service_availed
 * @property-read Intake|null $originalIntake
 * @property-read User|null $user
 */
#[Fillable([
    'original_intake_id', 'primary_therapist_id', 'user_id', 'assigned_therapist_id', 'assigned_at',
    'approved_date', 'clinical_notes', 'active_services', 'allergies', 'contract_start_date',
    'contract_end_date', 'signed_date', 'timeline', 'status', 'consents', 'service_availed',
])]
class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'approved_date' => 'datetime',
            'clinical_notes' => 'array',
            'allergies' => 'array',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'signed_date' => 'date',
            'timeline' => 'array',
            'consents' => 'array',
            'service_availed' => 'array',
        ];
    }

    /** @return BelongsTo<Intake, $this> */
    public function originalIntake(): BelongsTo
    {
        return $this->belongsTo(Intake::class, 'original_intake_id');
    }

    /** @return BelongsTo<User, $this> */
    public function primaryTherapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_therapist_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTherapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_therapist_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function careTeam(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user');
    }

    /** @return HasMany<ClientService, $this> */
    public function clientServices(): HasMany
    {
        return $this->hasMany(ClientService::class);
    }

    /** @return HasMany<ScheduleSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ScheduleSession::class);
    }

    /** @return HasOne<BillingAccount, $this> */
    public function billing(): HasOne
    {
        return $this->hasOne(BillingAccount::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** @return HasMany<ClientDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ClientDocument::class);
    }

    /** @return HasMany<Complaint, $this> */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function assignTherapist(User $therapist): void
    {
        $this->forceFill([
            'assigned_therapist_id' => $therapist->id,
            'primary_therapist_id' => $therapist->id,
            'assigned_at' => now()->toDateString(),
        ])->save();

        $this->careTeam()->syncWithoutDetaching([$therapist->id]);
    }

    public function reassignPrimaryTherapist(User $newTherapist, User $changedBy): void
    {
        $previousTherapistId = $this->primary_therapist_id;

        $this->forceFill([
            'primary_therapist_id' => $newTherapist->id,
            'assigned_therapist_id' => $newTherapist->id,
            'assigned_at' => now()->toDateString(),
        ]);

        $timeline = $this->timeline ?? [];
        $timeline[] = [
            'id' => (string) str()->uuid(),
            'action' => 'therapist_reassigned',
            'previous_therapist_id' => $previousTherapistId,
            'new_therapist_id' => $newTherapist->id,
            'changed_by' => $changedBy->id,
            'date' => now()->toDateTimeString(),
        ];
        $this->timeline = $timeline;
        $this->save();

        $this->careTeam()->syncWithoutDetaching([$newTherapist->id]);
    }

    /**
     * Rebuilds the denormalized `service_availed` cache from the
     * `client_services` relation, run after any ClientService mutation.
     */
    public function refreshServiceAvailedCache(): void
    {
        $this->update([
            'service_availed' => $this->clientServices()
                ->with(['service', 'therapist'])
                ->get()
                ->map(fn (ClientService $clientService): array => [
                    'id' => $clientService->id,
                    'service_id' => $clientService->service_id,
                    'service_name' => optional($clientService->service)->name,
                    'therapist_id' => $clientService->therapist_id,
                    'therapist_name' => optional($clientService->therapist)->full_name,
                    'frequency' => $clientService->frequency,
                    'duration' => $clientService->duration,
                    'start_date' => optional($clientService->start_date)->toDateString(),
                    'funding_source' => $clientService->funding_source,
                    'no_sessions' => $clientService->no_sessions,
                    'goals' => $clientService->goals,
                ])
                ->all(),
        ]);
    }
}
