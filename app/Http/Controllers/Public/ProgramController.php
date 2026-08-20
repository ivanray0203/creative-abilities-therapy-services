<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function index(): Response
    {
        $programs = Program::query()
            ->published()
            ->withCount(['registrations as taken_places' => fn ($query) => $query->whereIn('status', ['pending', 'confirmed'])])
            ->orderBy('starts_on')
            ->get()
            ->map(fn (Program $program): array => $this->summary($program));

        return Inertia::render('public/programs', [
            'programs' => $programs,
        ]);
    }

    /**
     * A programme's own page, carrying the registration form. Inactive
     * programmes 404 rather than rendering, so an unpublished draft can't be
     * reached by guessing its slug.
     */
    public function show(Program $program): Response
    {
        abort_unless($program->is_active, 404);

        return Inertia::render('public/program-detail', [
            'program' => [
                ...$this->summary($program),
                'description' => $program->description,
                'highlights' => $program->highlights ?? [],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Program $program): array
    {
        $capacity = $program->capacity;
        $taken = $program->taken_places ?? $program->takenPlaces();

        return [
            'id' => $program->id,
            'slug' => $program->slug,
            'name' => $program->name,
            'category' => $program->category,
            'summary' => $program->summary,
            'age_range' => $program->age_range,
            'schedule' => $program->schedule,
            'location' => $program->location,
            'price' => $program->price,
            'starts_on' => $program->starts_on?->toDateString(),
            'ends_on' => $program->ends_on?->toDateString(),
            'registration_closes_on' => $program->registration_closes_on?->toDateString(),
            'capacity' => $capacity,
            // Null for an open programme, so the page can say "open" rather
            // than implying a limit that doesn't exist.
            'places_left' => $capacity === null ? null : max(0, $capacity - $taken),
            'is_open' => $program->isOpenForRegistration(),
        ];
    }
}
