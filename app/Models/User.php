<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property string $role
 * @property bool $is_active
 * @property bool $new_intake
 * @property bool $invoice_payments
 * @property bool $session_reminders
 * @property bool $new_applications
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['email', 'first_name', 'last_name', 'phone', 'role', 'is_active', 'new_intake', 'invoice_payments', 'session_reminders', 'new_applications', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'new_intake' => 'boolean',
            'invoice_payments' => 'boolean',
            'session_reminders' => 'boolean',
            'new_applications' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTherapist(): bool
    {
        return $this->role === 'therapist';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /** @return HasOne<TeamMember, $this> */
    public function teamMember(): HasOne
    {
        return $this->hasOne(TeamMember::class);
    }

    /** @return HasOne<Client, $this> */
    public function clientProfile(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    /** @return HasMany<Intake, $this> */
    public function assignedIntakes(): HasMany
    {
        return $this->hasMany(Intake::class, 'assigned_therapist_id');
    }

    /** @return HasMany<Client, $this> */
    public function primaryClients(): HasMany
    {
        return $this->hasMany(Client::class, 'primary_therapist_id');
    }

    /** @return HasMany<Client, $this> */
    public function assignedClients(): HasMany
    {
        return $this->hasMany(Client::class, 'assigned_therapist_id');
    }

    /** @return HasMany<ScheduleSession, $this> */
    public function sessionsAsTherapist(): HasMany
    {
        return $this->hasMany(ScheduleSession::class, 'therapist_id');
    }

    /** @return HasMany<UserConsentAcceptance, $this> */
    public function consentAcceptances(): HasMany
    {
        return $this->hasMany(UserConsentAcceptance::class);
    }
}
