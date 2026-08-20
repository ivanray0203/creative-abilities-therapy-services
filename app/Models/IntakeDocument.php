<?php

namespace App\Models;

use Database\Factories\IntakeDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['intake_id', 'name', 'type', 'file', 'drive_file_id', 'drive_file_url', 'drive_web_view', 'uploaded_at'])]
class IntakeDocument extends Model
{
    /** @use HasFactory<IntakeDocumentFactory> */
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
}
