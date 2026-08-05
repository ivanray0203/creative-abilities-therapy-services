<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCareerRequest;
use App\Http\Requests\Admin\UpdateCareerRequest;
use App\Models\Career;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin position (job posting) management for the `careers` table that
 * backs the public Careers listing (App\Http\Controllers\Public\CareerController).
 */
class CareerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $careers = Career::query()
            ->when($search !== '', fn (Builder $query) => $query->where('position', 'like', "%{$search}%"))
            ->when($status !== 'all', fn (Builder $query) => $query->where('is_active', $status === 'active'))
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/careers/index', [
            'careers' => $careers,
            'stats' => [
                'total' => Career::query()->count(),
                'active' => Career::query()->where('is_active', true)->count(),
            ],
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/careers/create');
    }

    public function store(StoreCareerRequest $request): RedirectResponse
    {
        $career = Career::query()->create($request->validated());

        AuditLogger::log('Created position', 'Careers', "Created position #{$career->id}: {$career->position}");

        return to_route('admin.careers.index')->with('success', 'Position added successfully.');
    }

    public function edit(Career $career): Response
    {
        return Inertia::render('admin/careers/edit', [
            'career' => $career,
        ]);
    }

    public function update(UpdateCareerRequest $request, Career $career): RedirectResponse
    {
        $career->update($request->validated());

        AuditLogger::log('Updated position', 'Careers', "Updated position #{$career->id}: {$career->position}");

        return to_route('admin.careers.index')->with('success', 'Position updated successfully.');
    }

    public function destroy(Career $career): RedirectResponse
    {
        $position = $career->position;
        $career->delete();

        AuditLogger::log('Deleted position', 'Careers', "Deleted position #{$career->id}: {$position}", 'warning');

        return to_route('admin.careers.index')->with('success', 'Position removed successfully.');
    }
}
