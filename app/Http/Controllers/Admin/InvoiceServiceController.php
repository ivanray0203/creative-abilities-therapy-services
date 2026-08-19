<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceService;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The invoice rate card — every billable "Service Provided" line with its
 * FSCD and private/insurance rate.
 *
 * Only the rates are editable here. A line's name, code and discipline come
 * from the clinic's published rate sheet via InvoiceServiceSeeder, so they
 * stay in step with the sheet rather than drifting per edit.
 *
 * Per-therapist overrides of these rates live on the team member's Rates tab
 * (Admin\TeamMemberController::updateRates).
 */
class InvoiceServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $discipline = (string) $request->query('discipline', 'all');

        $services = InvoiceService::query()
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when($discipline !== 'all', fn (Builder $query) => $query->where('discipline', $discipline))
            ->orderBy('sort_order')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/services/index', [
            'services' => $services,
            'filters' => ['search' => $search, 'discipline' => $discipline],
            'disciplines' => InvoiceService::query()
                ->distinct()
                ->orderBy('discipline')
                ->pluck('discipline'),
        ]);
    }

    /**
     * Update one line's published rates.
     *
     * A blank rate is not zero — it means the line is not billable under that
     * funding stream, which is how the rate sheet leaves travel and mileage
     * blank under private funding.
     */
    public function updateRates(Request $request, InvoiceService $invoiceService): RedirectResponse
    {
        $validated = $request->validate([
            'rate_fscd' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'rate_private' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $invoiceService->update($validated);

        AuditLogger::log('Updated invoice service rates', 'Services', "Updated rates for invoice service #{$invoiceService->id}: {$invoiceService->name}");

        return back()->with('success', 'Rates updated successfully.');
    }
}
