<?php

namespace App\Models;

use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id', 'subject', 'description', 'status', 'type', 'admin_response', 'category',
    'resolve_at', 'therapist_id', 'session_id', 'complained_by', 'consent_given', 'consent_info',
    'consent_at', 'ip_address', 'file', 'drive_file_id', 'drive_file_url', 'drive_web_view',
    'reviewed_at', 'reviewed_by_id', 'resolved_by_id',
])]
class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'resolve_at' => 'datetime',
            'consent_given' => 'boolean',
            'consent_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<User, $this> */
    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    /** @return BelongsTo<ScheduleSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ScheduleSession::class, 'session_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
