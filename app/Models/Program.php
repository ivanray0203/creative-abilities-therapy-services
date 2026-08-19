<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<int, string>|null $highlights
 * @property CarbonInterface|null $starts_on
 * @property CarbonInterface|null $ends_on
 * @property CarbonInterface|null $registration_closes_on
 * @property-read Collection<int, ProgramRegistration> $registrations
 */
#[Fillable([
    'name', 'slug', 'category', 'summary', 'description', 'age_range', 'schedule', 'location',
    'highlights', 'capacity', 'price', 'starts_on', 'ends_on', 'registration_closes_on', 'is_active',
])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'capacity' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'registration_closes_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** Route model binding resolves the public URLs by slug, not id. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<ProgramRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(ProgramRegistration::class);
    }

    /**
     * @param  Builder<Program>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Registrations that count against capacity — a cancelled one frees its
     * place again.
     */
    public function takenPlaces(): int
    {
        return $this->registrations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
    }

    /**
     * Whether the public form should still accept a sign-up: the programme
     * has to be open, within its registration window, and under capacity.
     */
    public function isOpenForRegistration(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $closesOn = $this->registration_closes_on ?? $this->starts_on;

        if ($closesOn !== null && $closesOn->isBefore(now()->startOfDay())) {
            return false;
        }

        return $this->capacity === null || $this->takenPlaces() < $this->capacity;
    }
}
