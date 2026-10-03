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
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property string $role The primary role: decides the portal the user lands on and acts as. Every role they hold lives in Spatie's tables.
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
#[Hidden(['password', 'remember_token', 'roles', 'permissions'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The roles every install has; seeded by the permission tables migration.
     *
     * @var array<int, string>
     */
    public const ROLES = ['admin', 'therapist', 'client'];

    /**
     * The role the user is acting in for this request, set by EnsureRole
     * from the portal being visited. Null outside the role-guarded portals.
     */
    protected ?string $actingRole = null;

    /**
     * Keeps the primary `role` column and the Spatie roles in step: a new
     * user is granted their primary role, and changing the primary role
     * swaps the grant. Roles assigned on top of it are left alone.
     */
    protected static function booted(): void
    {
        static::created(function (User $user): void {
            $user->assignRole(Role::findOrCreate($user->getAttribute('role') ?? 'client'));
        });

        static::updated(function (User $user): void {
            if (! $user->wasChanged('role')) {
                return;
            }

            $user->removeRole(Role::findOrCreate($user->getOriginal('role')));
            $user->assignRole(Role::findOrCreate($user->role));
        });
    }

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

    /**
     * Inside a portal these three answer for the portal being visited, not
     * for every role held: someone who is both an admin and a therapist is
     * a therapist, and only a therapist, while in the therapist portal.
     * Outside a portal they answer for any role the user holds.
     */
    public function isAdmin(): bool
    {
        return $this->actsAs('admin');
    }

    public function isTherapist(): bool
    {
        return $this->actsAs('therapist');
    }

    public function isClient(): bool
    {
        return $this->actsAs('client');
    }

    public function actAs(string $role): static
    {
        $this->actingRole = $role;

        return $this;
    }

    /** The role being acted in: the portal's, or the primary role outside one. */
    public function actingRole(): string
    {
        return $this->actingRole ?? $this->role;
    }

    private function actsAs(string $role): bool
    {
        return $this->actingRole !== null ? $this->actingRole === $role : $this->hasRole($role);
    }

    /**
     * A therapist holding an aide position, who logs hours on a time sheet
     * rather than billing services. A therapist with no team-member record
     * is not an aide — they keep Billing and Invoices.
     */
    public function isAide(): bool
    {
        return $this->isTherapist() && $this->teamMember?->isAide() === true;
    }

    /**
     * A candidate who has signed their offer and been given a portal account
     * but whose documents an admin has not yet reviewed. Only their profile
     * is reachable until they are hired.
     */
    public function isOnboarding(): bool
    {
        return $this->isTherapist() && $this->teamMember?->employment_status === 'onboarding';
    }

    /** @return HasOne<TeamMember, $this> */
    public function teamMember(): HasOne
    {
        return $this->hasOne(TeamMember::class);
    }

    /**
     * A parent may have several children in care, each its own Client record
     * (Phase 17). Use ClientContext to resolve which one the portal is
     * currently scoped to.
     *
     * @return HasMany<Client, $this>
     */
    public function clientProfiles(): HasMany
    {
        return $this->hasMany(Client::class);
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

    /**
     * Contracts this therapist is authorized on — the snapshot taken when each
     * was issued, not the availed service's current assignment.
     *
     * @return HasMany<ServiceContract, $this>
     */
    public function serviceContracts(): HasMany
    {
        return $this->hasMany(ServiceContract::class, 'therapist_id');
    }
}
