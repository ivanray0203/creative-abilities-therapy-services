<?php

namespace App\Models;

use Database\Factories\ConsentDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $effective_date
 */
#[Fillable(['title', 'is_active', 'purpose', 'version', 'effective_date'])]
class ConsentDocument extends Model
{
    /** @use HasFactory<ConsentDocumentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'effective_date' => 'date',
        ];
    }

    public function getIsCurrentAttribute(): bool
    {
        return $this->is_active && $this->effective_date->lte(Carbon::today());
    }

    /** @return HasMany<ConsentClause, $this> */
    public function clauses(): HasMany
    {
        return $this->hasMany(ConsentClause::class, 'document_id')->orderBy('order');
    }

    /** @return HasMany<UserConsentAcceptance, $this> */
    public function userAcceptances(): HasMany
    {
        return $this->hasMany(UserConsentAcceptance::class, 'document_id');
    }
}
