<?php

namespace App\Models;

use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<int, string>|null $credentials
 * @property array<int, string>|null $specializations
 * @property array<int, int>|null $client
 * @property array<int, array<string, mixed>>|null $availability
 * @property array<int, array<string, mixed>>|null $documents
 * @property array<int, string>|null $required_documents
 */
#[Fillable([
    'user_id', 'position', 'resident_status', 'department', 'employment_status', 'hire_date',
    'hourly_rate', 'maximum_caseload', 'credentials', 'specializations', 'emergency_contact_name',
    'emergency_contact_phone', 'can_access_finance', 'can_manage_team', 'can_manage_clients',
    'additional_notes', 'title', 'description', 'photo', 'client', 'application_id', 'phone',
    'office_phone', 'street_address', 'address_line_2', 'city', 'province', 'zip_code',
    'availability', 'documents', 'secondary_email', 'birthdate', 'required_documents',
    'sin_number', 'license_number', 'years_of_experience',
])]
class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'hourly_rate' => 'decimal:2',
            'maximum_caseload' => 'integer',
            'credentials' => 'array',
            'specializations' => 'array',
            'can_access_finance' => 'boolean',
            'can_manage_team' => 'boolean',
            'can_manage_clients' => 'boolean',
            'client' => 'array',
            'availability' => 'array',
            'documents' => 'array',
            'birthdate' => 'date',
            'required_documents' => 'array',
        ];
    }

    /**
     * SIN is stored one-way hashed (SHA-256), matching the reference
     * implementation — plaintext is never persisted or retrievable.
     */
    public function setSinNumberAttribute(?string $value): void
    {
        $this->attributes['sin_number'] = $value === null ? null : hash('sha256', $value);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
