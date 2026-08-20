<?php

namespace App\Models;

use Database\Factories\UserConsentAcceptanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_id', 'user_id', 'accepted_at', 'revoked_at', 'is_revoked'])]
class UserConsentAcceptance extends Model
{
    /** @use HasFactory<UserConsentAcceptanceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'is_revoked' => 'boolean',
        ];
    }

    /** @return BelongsTo<ConsentDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ConsentDocument::class, 'document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revoke(): void
    {
        $this->forceFill([
            'is_revoked' => true,
            'revoked_at' => now(),
        ])->save();
    }
}
