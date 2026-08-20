<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Therapist-facing client list (reference: cats-frontend/src/pages/therapist/ClientsPage.tsx).
 * Scoped to clients where the therapist is the primary/assigned therapist
 * or a care-team member.
 */
class TherapistClientController extends Controller
{
    public function index(Request $request): Response
    {
        $therapistId = $request->user()->id;
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'active');

        $clients = Client::query()
            ->forTherapist($therapistId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('originalIntake', function (Builder $inner) use ($search): void {
                    $inner->where('child_first_name', 'like', "%{$search}%")
                        ->orWhere('child_last_name', 'like', "%{$search}%")
                        ->orWhere('primary_parent_name', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn (Builder $query) => $query->where('status', 'active'))
            ->when($status === 'inactive', fn (Builder $query) => $query->whereIn('status', ['inactive', 'paused', 'completed', 'archive']))
            ->with(['originalIntake', 'clientServices.service'])
            // Drives the row's "Create Session" action: a client whose
            // services this therapist has all booked has nothing left to
            // schedule, and the session form would not offer them anyway.
            ->withExists(['clientServices as has_bookable_service' => function (Builder $services) use ($therapistId): void {
                $services->where('therapist_id', $therapistId)->whereIn(
                    'client_services.id',
                    ClientService::query()->select('id')->awaitingSchedule(),
                );
            }])
            // No `distinct()`: `forTherapist` scopes with subqueries rather
            // than a join, so no row is duplicated — and `distinct` makes
            // paginate()'s count disagree with the rows it returns.
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('therapist/clients/index', [
            'clients' => $clients,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }
}
