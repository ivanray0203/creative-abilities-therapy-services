<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property array<int, array<string, mixed>>|null $internal_notes
 * @property array<int, array<string, mixed>>|null $notes
 * @property array<int, string>|null $skills
 * @property array<int, array<string, mixed>>|null $availability
 * @property array<int, array<string, mixed>>|null $references
 * @property Carbon|null $preferred_start_date
 * @property Carbon|null $interview_date
 * @property Carbon|null $interview_time
 * @property Carbon|null $hire_date
 * @property Carbon|null $offer_sent_at
 * @property Carbon|null $offer_expires_at
 * @property Carbon|null $offer_accepted_at
 * @property Carbon|null $offer_declined_at
 * @property-read string|null $interview_schedule
 * @property-read string|null $interview_platform_label
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
    'reference_number', 'offer_sent_at', 'offer_expires_at', 'offer_letter',
    'offer_letter_drive_file_id', 'signed_offer_letter', 'signed_offer_letter_drive_file_id',
    'offer_accepted_at', 'offer_declined_at',
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
            'offer_sent_at' => 'datetime',
            'offer_expires_at' => 'datetime',
            'offer_accepted_at' => 'datetime',
            'offer_declined_at' => 'datetime',
        ];
    }

    /**
     * Appended so the admin screens and the candidate's email read the
     * interview off exactly the same string. Formatting here rather than in
     * the browser also keeps the clock out of the viewer's timezone, which
     * resources/js/lib/helpers.ts documents at length as a source of
     * off-by-one displays.
     *
     * @var list<string>
     */
    protected $appends = ['interview_schedule', 'interview_platform_label'];

    /**
     * The booked interview as one readable phrase, e.g.
     * "Wednesday, 2 September 2026 at 2:00 PM". Date and time live in
     * separate columns and the time is optional, so callers that just want
     * to show the slot should not have to reassemble it.
     */
    public function getInterviewScheduleAttribute(): ?string
    {
        if ($this->interview_date === null) {
            return null;
        }

        $date = $this->interview_date->format('l, j F Y');

        if ($this->interview_time === null) {
            return $date;
        }

        return "{$date} at {$this->interview_time->format('g:i A')}";
    }

    /** Readable label for the stored interview_platform value. */
    public function getInterviewPlatformLabelAttribute(): ?string
    {
        return match ($this->interview_platform) {
            'video' => 'Video Call',
            'phone' => 'Phone Call',
            'in-person' => 'In-Person',
            default => $this->interview_platform,
        };
    }

    /** An offer is outstanding once it has been sent and neither signed nor turned down. */
    public function hasOpenOffer(): bool
    {
        return $this->offer_sent_at !== null
            && $this->offer_accepted_at === null
            && $this->offer_declined_at === null;
    }

    /** True once the candidate has signed and the signed copy is on file. */
    public function hasSignedOffer(): bool
    {
        return $this->offer_accepted_at !== null && $this->signed_offer_letter !== null;
    }

    /** An open offer whose deadline has passed can no longer be signed. */
    public function offerHasExpired(): bool
    {
        return $this->hasOpenOffer()
            && $this->offer_expires_at !== null
            && $this->offer_expires_at->isPast();
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
