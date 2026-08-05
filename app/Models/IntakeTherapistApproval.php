<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['intake_id', 'therapist_id', 'service', 'status', 'notes', 'decided_at'])]
class IntakeTherapistApproval extends Model
{
    /** @use HasFactory<\Database\Factories\IntakeTherapistApprovalFactory> */
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

    /**
     * Status of the most recent history entry logged for this review's own
     * (intake, service) pair — used to gate whether the therapist should see
     * a decision prompt for this specific service.
     */
    public function latestHistoryStatus(): ?string
    {
        return IntakeTherapistApprovalHistory::query()
            ->where('intake_id', $this->intake_id)
            ->where('service', $this->service)
            ->latest('created_at')
            ->value('status');
    }
}
