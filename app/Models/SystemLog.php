<?php

namespace App\Models;

use Database\Factories\SystemLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $details
 */
#[Fillable(['user_id', 'action', 'details', 'ip_address'])]
class SystemLog extends Model
{
    /** @use HasFactory<SystemLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
