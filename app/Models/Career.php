<?php

namespace App\Models;

use App\Casts\StringList;
use Database\Factories\CareerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<int, string>|null $responsibilities
 * @property array<int, string>|null $qualifications
 * @property array<int, string>|null $skills
 * @property array<int, string>|null $benefits
 * @property array<int, string>|null $highlights
 * @property array<int, string>|null $required_documents
 * @property array<string, mixed>|null $detail
 */
#[Fillable([
    'position', 'location', 'schedule', 'contract', 'rate', 'short_description', 'about_description',
    'responsibilities', 'qualifications', 'skills', 'benefits', 'is_active', 'sort_order', 'due_date',
    'highlights', 'level', 'hours', 'required_documents', 'detail',
])]
class Career extends Model
{
    /** @use HasFactory<CareerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'responsibilities' => StringList::class,
            'qualifications' => StringList::class,
            'skills' => StringList::class,
            'benefits' => StringList::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'due_date' => 'date',
            'highlights' => StringList::class,
            'required_documents' => StringList::class,
            'detail' => 'array',
        ];
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'position_id');
    }
}
