<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_id', 'order', 'text_template'])]
class ConsentClause extends Model
{
    /** @use HasFactory<\Database\Factories\ConsentClauseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    /** @return BelongsTo<ConsentDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ConsentDocument::class, 'document_id');
    }
}
