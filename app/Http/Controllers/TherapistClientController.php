<?php

namespace App\Http\Controllers;

use App\Models\Client;
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
            ->distinct()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('therapist/clients/index', [
            'clients' => $clients,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }
}
