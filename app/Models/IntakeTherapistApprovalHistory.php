<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['intake_id', 'therapist_id', 'service', 'status', 'notes', 'decided_at'])]
class IntakeTherapistApprovalHistory extends Model
{
    /** @use HasFactory<\Database\Factories\IntakeTherapistApprovalHistoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Intake, $this> */
    public function intake(): BelongsTo
    {
        return $this->belongsTo(Intake::class);
    }

    /** @return BelongsTo<User, $this> */
    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }
}
