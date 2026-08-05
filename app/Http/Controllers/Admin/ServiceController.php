<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin Services visibility toggle (reference: cats-frontend's
 * administratorTabs/ServicesTab.tsx). Toggling `is_active` here is
 * reflected immediately on the public Services page
 * (Public\ServiceController::index()'s `where('is_active', true)` scope).
 */
class ServiceController extends Controller
{
    public function updateVisibility(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $service->update($validated);

        $state = $validated['is_active'] ? 'active' : 'inactive';
        AuditLogger::log('Updated service visibility', 'Services', "Set service #{$service->id} to {$state}");

        return back()->with('success', 'Service visibility updated.');
    }
}
