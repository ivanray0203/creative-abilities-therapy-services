<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Models\ScheduleSession;
use App\Models\User;
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
                // Drives the therapist sidebar: an onboarding candidate sees
                // only Profile until an admin has reviewed their documents.
                'is_onboarding' => $user?->isOnboarding() === true,
                'client_id' => $currentChild?->id,
                'children' => $children->map(fn (Client $child): array => [
                    'id' => $child->id,
                    'name' => $child->displayName(),
                ])->values(),
                // The other portals this user's roles open, for the sidebar's
                // "Switch to ..." links. A closure because the portal being
                // visited is only known once the route's role middleware ran.
                'portals' => fn (): array => $user === null ? [] : $this->otherPortals($user),
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

    /**
     * @return array<int, array{role: string, url: string}>
     */
    private function otherPortals(User $user): array
    {
        return $user->getRoleNames()
            ->reject(fn (string $role): bool => $role === $user->actingRole() || EnsureRole::homeFor($role) === '/')
            ->map(fn (string $role): array => ['role' => $role, 'url' => EnsureRole::homeFor($role)])
            ->values()
            ->all();
    }
}
