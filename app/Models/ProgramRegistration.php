<?php

namespace App\Models;

use Database\Factories\ProgramRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $participant_date_of_birth
 * @property-read Program|null $program
 */
#[Fillable([
    'reference_number', 'program_id', 'participant_first_name', 'participant_last_name',
    'participant_date_of_birth', 'parent_name', 'parent_email', 'parent_phone', 'notes', 'status',
])]
class ProgramRegistration extends Model
{
    /** @use HasFactory<ProgramRegistrationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'participant_date_of_birth' => 'date',
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function participantName(): string
    {
        return trim("{$this->participant_first_name} {$this->participant_last_name}");
    }
}
