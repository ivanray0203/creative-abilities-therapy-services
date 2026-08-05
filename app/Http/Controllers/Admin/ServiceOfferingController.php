<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceOffering;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin "Service Offerings" catalog — reference: cats-frontend/src/pages/admin/Services.tsx.
 * Distinct from Admin\ServiceController, which only toggles visibility for
 * the public marketing `Service` model; this manages the internal
 * `ServiceOffering` catalog used when scheduling sessions/invoices.
 */
class ServiceOfferingController extends Controller
{
    public function index(Request $request): Response
    {
        $type = (string) $request->query('type', 'general_service');

        $stats = [
            'general_service' => ServiceOffering::query()->where('type', 'general_service')->count(),
            'specific_service' => ServiceOffering::query()->where('type', 'specific_service')->count(),
            'non_direct_service' => ServiceOffering::query()->where('type', 'non_direct_service')->count(),
        ];

        $services = ServiceOffering::query()
            ->where('type', $type)
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/services/index', [
            'services' => $services,
            'stats' => $stats,
            'filters' => ['type' => $type],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/services/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $service = ServiceOffering::query()->create($this->validated($request));

        AuditLogger::log('Created service offering', 'Services', "Created service offering #{$service->id}: {$service->name}");

        return to_route('admin.services.index')->with('success', 'Service created successfully.');
    }

    public function edit(ServiceOffering $service): Response
    {
        return Inertia::render('admin/services/edit', [
            'service' => $service,
        ]);
    }

    public function update(Request $request, ServiceOffering $service): RedirectResponse
    {
        $service->update($this->validated($request, $service));

        AuditLogger::log('Updated service offering', 'Services', "Updated service offering #{$service->id}: {$service->name}");

        return to_route('admin.services.index')->with('success', 'Service updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ServiceOffering $service = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:80', Rule::unique('service_offerings', 'code')->ignore($service)],
            'type' => ['required', Rule::in(['general_service', 'specific_service', 'non_direct_service'])],
            'description' => ['nullable', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
