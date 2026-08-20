<?php

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Every role-scoped route, probed as every other role.
 *
 * Route lists grow by hand, so a guard is only ever one forgotten
 * `->middleware()` away from missing. This walks the router itself rather
 * than a list someone has to remember to update.
 */
$roleHomes = [
    'admin' => EnsureRole::homeFor('admin'),
    'therapist' => EnsureRole::homeFor('therapist'),
    'client' => EnsureRole::homeFor('client'),
];

/**
 * `isRedirect()` matches on the absolute URL, so compare the path alone.
 */
function redirectsTo(TestResponse $response, string $path): bool
{
    if (! $response->isRedirect()) {
        return false;
    }

    return parse_url((string) $response->headers->get('Location'), PHP_URL_PATH) === $path;
}

/**
 * @return array<int, array{method: string, uri: string, role: string, name: string|null}>
 */
function roleScopedRoutes(): array
{
    $routes = [];

    foreach (Route::getRoutes() as $route) {
        $role = collect($route->gatherMiddleware())
            ->first(fn ($middleware): bool => is_string($middleware) && str_starts_with($middleware, 'role:'));

        if ($role === null) {
            continue;
        }

        foreach ($route->methods() as $method) {
            if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                continue;
            }

            $routes[] = [
                'method' => $method,
                // Any id will do: the role guard runs before model binding,
                // so a wrong-role request must be turned away whether or not
                // the record exists.
                'uri' => '/'.Str::of($route->uri())->replaceMatches('/\{[^}]+\}/', '1'),
                'role' => Str::after($role, 'role:'),
                'name' => $route->getName(),
            ];
        }
    }

    return $routes;
}

test('every role-scoped route turns away the other two roles', function () use ($roleHomes) {
    $users = [
        'admin' => User::factory()->admin()->create(),
        'therapist' => User::factory()->therapist()->create(),
        'client' => User::factory()->client()->create(),
    ];

    $routes = roleScopedRoutes();
    expect($routes)->not->toBeEmpty();

    $leaks = [];

    foreach ($routes as $route) {
        foreach ($users as $role => $user) {
            if ($role === $route['role']) {
                continue;
            }

            $response = $this->actingAs($user)->call($route['method'], $route['uri']);

            // The role middleware bounces to the caller's own home. Anything
            // that is not a redirect away means the guard let them through.
            $isTurnedAway = redirectsTo($response, $roleHomes[$role])
                || in_array($response->status(), [401, 403, 404], true);

            if (! $isTurnedAway) {
                $leaks[] = sprintf(
                    '%s %s (%s) reachable by %s — status %s',
                    $route['method'],
                    $route['uri'],
                    $route['role'],
                    $role,
                    $response->status(),
                );
            }
        }
    }

    expect($leaks)->toBe([]);
});

test('every role-scoped route turns away a guest', function () {
    $leaks = [];

    foreach (roleScopedRoutes() as $route) {
        $response = $this->call($route['method'], $route['uri']);

        if (! redirectsTo($response, '/login') && $response->status() !== 401) {
            $leaks[] = sprintf(
                '%s %s reachable by guest — status %s',
                $route['method'],
                $route['uri'],
                $response->status(),
            );
        }
    }

    expect($leaks)->toBe([]);
});

test('an inactive account cannot use the app', function () {
    $suspended = User::factory()->therapist()->create(['is_active' => false]);

    $this->actingAs($suspended)->get('/therapist')->assertRedirect();
});
