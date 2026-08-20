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
 * The internal `ServiceOffering` catalog used when scheduling sessions.
 *
 * Listing lives on Admin\InvoiceServiceController, which owns /admin/services;
 * these forms are reached directly by URL.
 */
class ServiceOfferingController extends Controller
{
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
