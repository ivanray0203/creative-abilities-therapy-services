<?php

namespace App\Models;

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
 */
#[Fillable([
    'position', 'location', 'schedule', 'contract', 'rate', 'short_description', 'about_description',
    'responsibilities', 'qualifications', 'skills', 'benefits', 'is_active', 'due_date',
    'highlights', 'level', 'hours', 'required_documents',
])]
class Career extends Model
{
    /** @use HasFactory<\Database\Factories\CareerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'responsibilities' => 'array',
            'qualifications' => 'array',
            'skills' => 'array',
            'benefits' => 'array',
            'is_active' => 'boolean',
            'due_date' => 'date',
            'highlights' => 'array',
            'required_documents' => 'array',
        ];
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'position_id');
    }
}
