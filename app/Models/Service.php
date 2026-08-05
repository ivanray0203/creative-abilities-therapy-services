<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<int, string>|null $benefits
 * @property array<int, string>|null $offerings
 * @property array<int, string>|null $approaches
 * @property array<int, string>|null $outcomes
 * @property array<int, string>|null $area_of_focus
 * @property array<int, string>|null $tags
 */
#[Fillable([
    'name', 'code', 'short_description', 'description', 'duration_minutes', 'base_price',
    'is_active', 'benefits', 'offerings', 'approaches', 'outcomes', 'area_of_focus',
    'description_highlight', 'tags', 'ages', 'signs_to_look_for', 'conditions', 'frequency',
    'location', 'main_tag', 'duration', 'photo',
])]
class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
            'benefits' => 'array',
            'offerings' => 'array',
            'approaches' => 'array',
            'outcomes' => 'array',
            'area_of_focus' => 'array',
            'tags' => 'array',
        ];
    }
}
