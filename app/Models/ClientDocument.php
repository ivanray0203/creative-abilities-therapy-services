<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'intake_id', 'client_id', 'user_id', 'doc_type', 'title', 'upload_origin', 'drive_file_id',
    'drive_file_url', 'drive_web_view', 'notes', 'uploaded_by_id', 'uploaded_at',
])]
class ClientDocument extends Model
{
    /** @use HasFactory<\Database\Factories\ClientDocumentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Intake, $this> */
    public function intake(): BelongsTo
    {
        return $this->belongsTo(Intake::class);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
