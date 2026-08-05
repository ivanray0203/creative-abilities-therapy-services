<?php

namespace App\Models;

use Database\Factories\IntakeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property array<int, string>|null $services_needed
 * @property array<int, string>|null $diagnosis
 * @property array<int, string>|null $available_days
 * @property array<int, string>|null $preferred_times
 * @property array<int, array<string, mixed>>|null $notes
 * @property array<int, array<string, mixed>>|null $timeline
 * @property array<string, mixed>|null $funding_source_info
 * @property array<int, int>|null $consents
 */
#[Fillable([
    'submitted_by_id', 'completed', 'child_first_name', 'child_middle_name', 'child_last_name',
    'date_of_birth', 'age', 'gender', 'status', 'street_address', 'address_line_2', 'city',
    'state_province', 'postal_code', 'grade_level', 'school_name', 'services_needed',
    'currently_receiving_services', 'diagnosis', 'has_medical_conditions', 'languages_spoken_at_home',
    'require_interpreter', 'funding_source', 'available_days', 'preferred_times',
    'primary_parent_name', 'primary_parent_phone', 'primary_parent_email', 'primary_relationship_to_child',
    'primary_contact_method', 'secondary_parent_name', 'secondary_parent_phone', 'secondary_parent_email',
    'secondary_relationship_to_child', 'secondary_contact_method', 'additional_information', 'reviewed',
    'approved_as_client', 'linked_client_id', 'medical_conditions', 'interpreter_needed',
    'receiving_services_desc', 'admin_addition_informations', 'theraphy_goals', 'referral_source',
    'funding_number', 'annual_funding', 'notes', 'timeline', 'emergency_contact_name',
    'emergency_contact_relationship', 'emergency_contact_phone', 'funding_source_info',
    'assigned_therapist_id', 'assigned_at', 'reference_number', 'consents',
])]
class Intake extends Model
{
    /** @use HasFactory<IntakeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'date_of_birth' => 'date',
            'age' => 'integer',
            'services_needed' => 'array',
            'currently_receiving_services' => 'boolean',
            'diagnosis' => 'array',
            'has_medical_conditions' => 'boolean',
            'require_interpreter' => 'boolean',
            'available_days' => 'array',
            'preferred_times' => 'array',
            'additional_information' => 'string',
            'reviewed' => 'boolean',
            'approved_as_client' => 'boolean',
            'notes' => 'array',
            'timeline' => 'array',
            'funding_source_info' => 'array',
            'assigned_at' => 'date',
            'consents' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTherapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_therapist_id');
    }

    /** @return HasMany<IntakeDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(IntakeDocument::class);
    }

    /** @return HasOne<Client, $this> */
    public function promotedClient(): HasOne
    {
        return $this->hasOne(Client::class, 'original_intake_id');
    }

    /** @return HasMany<IntakeTherapistApproval, $this> */
    public function therapistReviews(): HasMany
    {
        return $this->hasMany(IntakeTherapistApproval::class);
    }

    /** @return HasMany<IntakeTherapistApprovalHistory, $this> */
    public function therapistReviewHistory(): HasMany
    {
        return $this->hasMany(IntakeTherapistApprovalHistory::class);
    }
}
