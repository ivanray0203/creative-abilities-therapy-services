<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Models\ScheduleSession;
use App\Services\ClientContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(private ClientContext $clientContext) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Only parents have children to resolve — skip the queries entirely
        // for guests, admins, and therapists.
        $children = $user?->isClient() === true
            ? $this->clientContext->children($user)
            : collect();

        $currentChild = $user?->isClient() === true
            ? $this->clientContext->current($user)
            : null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'organization' => config('cats'),
            'auth' => [
                'user' => $user,
                'team_member' => $user?->teamMember,
                'client_id' => $currentChild?->id,
                'children' => $children->map(fn (Client $child): array => [
                    'id' => $child->id,
                    'name' => $child->displayName(),
                ])->values(),
            ],
            'activeSession' => $user?->isTherapist()
                ? ScheduleSession::query()
                    ->where('therapist_id', $user->id)
                    ->where('status', 'inprogress')
                    ->with(['client.originalIntake', 'service'])
                    ->first()
                : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                // Intake submissions flash this for the confirmation modal;
                // without it here the modal's "Reference Number" line could
                // never render.
                'reference_number' => fn () => $request->session()->get('reference_number'),
            ],
        ];
    }
}
