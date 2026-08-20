<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Models\Program;
use App\Models\ProgramRegistration;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin management for the `programs` table behind the public Programs pages
 * (App\Http\Controllers\Public\ProgramController), plus the registrations
 * families submit against them.
 */
class ProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $programs = Program::query()
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status !== 'all', fn (Builder $query) => $query->where('is_active', $status === 'active'))
            ->withCount([
                'registrations',
                'registrations as taken_places' => fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed']),
            ])
            ->orderBy('starts_on')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/programs/index', [
            'programs' => $programs,
            'stats' => [
                'total' => Program::query()->count(),
                'active' => Program::query()->where('is_active', true)->count(),
                'registrations' => ProgramRegistration::query()->whereNot('status', 'cancelled')->count(),
                'pending' => ProgramRegistration::query()->where('status', 'pending')->count(),
            ],
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/programs/create');
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $program = Program::query()->create($request->validated());

        AuditLogger::log('Created program', 'Services', "Created program #{$program->id}: {$program->name}");

        return to_route('admin.programs.index')->with('success', 'Program added successfully.');
    }

    /**
     * A program plus the families registered for it — the registrations are
     * the reason to open a program at all, so they live on the same page
     * rather than behind another click.
     */
    public function show(Program $program): Response
    {
        return Inertia::render('admin/programs/show', [
            'program' => [
                ...$program->only([
                    'id', 'slug', 'name', 'category', 'summary', 'description', 'age_range',
                    'schedule', 'location', 'capacity', 'price', 'is_active',
                ]),
                'highlights' => $program->highlights ?? [],
                'starts_on' => $program->starts_on?->toDateString(),
                'ends_on' => $program->ends_on?->toDateString(),
                'registration_closes_on' => $program->registration_closes_on?->toDateString(),
                'taken_places' => $program->takenPlaces(),
                'is_open' => $program->isOpenForRegistration(),
            ],
            'registrations' => $program->registrations()
                ->latest('created_at')
                ->get()
                ->map(fn (ProgramRegistration $registration): array => [
                    'id' => $registration->id,
                    'reference_number' => $registration->reference_number,
                    'participant' => $registration->participantName(),
                    'participant_date_of_birth' => $registration->participant_date_of_birth?->toDateString(),
                    'parent_name' => $registration->parent_name,
                    'parent_email' => $registration->parent_email,
                    'parent_phone' => $registration->parent_phone,
                    'notes' => $registration->notes,
                    'status' => $registration->status,
                    'registered_on' => $registration->created_at?->toDateString(),
                ]),
        ]);
    }

    public function edit(Program $program): Response
    {
        return Inertia::render('admin/programs/edit', [
            'program' => [
                ...$program->only([
                    'id', 'slug', 'name', 'category', 'summary', 'description', 'age_range',
                    'schedule', 'location', 'capacity', 'price', 'is_active',
                ]),
                'highlights' => $program->highlights ?? [],
                'starts_on' => $program->starts_on?->toDateString(),
                'ends_on' => $program->ends_on?->toDateString(),
                'registration_closes_on' => $program->registration_closes_on?->toDateString(),
            ],
        ]);
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->validated());

        AuditLogger::log('Updated program', 'Services', "Updated program #{$program->id}: {$program->name}");

        return to_route('admin.programs.index')->with('success', 'Program updated successfully.');
    }

    /**
     * Deleting cascades to the registrations, so a program families have
     * already signed up for is deactivated instead — the sign-ups are records
     * of a commitment, not disposable rows.
     */
    public function destroy(Program $program): RedirectResponse
    {
        if ($program->registrations()->exists()) {
            $program->update(['is_active' => false]);

            AuditLogger::log(
                'Deactivated program',
                'Services',
                "Deactivated program #{$program->id}: {$program->name} (has registrations)",
                'warning',
            );

            return to_route('admin.programs.index')
                ->with('success', 'Program has registrations, so it was deactivated rather than deleted.');
        }

        $name = $program->name;
        $program->delete();

        AuditLogger::log('Deleted program', 'Services', "Deleted program #{$program->id}: {$name}", 'warning');

        return to_route('admin.programs.index')->with('success', 'Program removed successfully.');
    }

    public function updateRegistrationStatus(Request $request, Program $program, ProgramRegistration $registration): RedirectResponse
    {
        abort_unless($registration->program_id === $program->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,waitlisted,cancelled'],
        ]);

        $registration->update(['status' => $validated['status']]);

        AuditLogger::log(
            'Updated program registration',
            'Services',
            "Registration {$registration->reference_number} set to {$validated['status']}",
        );

        return back()->with('success', 'Registration updated.');
    }
}
