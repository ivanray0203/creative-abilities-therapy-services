<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('public/services', [
            'services' => Service::query()->where('is_active', true)->get(),
        ]);
    }

    /**
     * Renders a single service. If the id doesn't match a database row, the
     * page falls back to a static entry from lib/content/services-fallback.ts,
     * exactly like the reference's client-side fallback behaviour.
     */
    public function show(string $service): Response
    {
        return Inertia::render('public/service-detail', [
            'service' => Service::query()->find($service),
            'serviceId' => (int) $service,
        ]);
    }
}
