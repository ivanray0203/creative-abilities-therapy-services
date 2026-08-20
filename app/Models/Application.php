<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<int, array<string, mixed>>|null $internal_notes
 * @property array<int, array<string, mixed>>|null $notes
 * @property array<int, string>|null $skills
 * @property array<int, array<string, mixed>>|null $availability
 * @property array<int, array<string, mixed>>|null $references
 */
#[Fillable([
    'first_name', 'middle_name', 'last_name', 'phone', 'email', 'street_address', 'address_line_2',
    'city', 'province', 'zip_code', 'position_applied', 'position_id', 'profession_status',
    'preferred_start_date', 'is_working_with_other', 'resume', 'resume_drive_file_id', 'cover_letter',
    'cover_letter_drive_file_id', 'drivers_license',
    'has_vehicle', 'lead_source', 'reason_for_applying', 'other_notes', 'application_status',
    'internal_notes', 'notes', 'experience', 'expected_salary', 'notice_availability', 'hourly_rate',
    'hire_date', 'interview_date', 'interview_time', 'interview_platform', 'education', 'skills',
    'candidate_rating', 'hired', 'declined', 'availability', 'references', 'resident_status',
    'reference_number',
])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'preferred_start_date' => 'date',
            'is_working_with_other' => 'boolean',
            'drivers_license' => 'boolean',
            'has_vehicle' => 'boolean',
            'internal_notes' => 'array',
            'notes' => 'array',
            'hourly_rate' => 'decimal:2',
            'hire_date' => 'date',
            'interview_date' => 'date',
            'interview_time' => 'datetime:H:i',
            'skills' => 'array',
            'candidate_rating' => 'integer',
            'hired' => 'boolean',
            'declined' => 'boolean',
            'availability' => 'array',
            'references' => 'array',
        ];
    }

    /** @return BelongsTo<Career, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Career::class, 'position_id');
    }

    /** @return HasMany<TeamMember, $this> */
    public function teamMember(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'application_id');
    }
}
