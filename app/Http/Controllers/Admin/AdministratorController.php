<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin "Administrator" settings/audit panel (reference:
 * cats-frontend/src/pages/admin/AdministratorPage.tsx and its 4
 * administratorTabs/*.tsx). All 4 tabs share one Inertia render since each
 * dataset is small — no need for deferred props.
 */
class AdministratorController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $module = (string) $request->query('module', 'all');

        $logs = SystemLog::query()
            ->with('user')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('action', 'like', "%{$search}%")
                        ->orWhere('details->user_email', 'like', "%{$search}%")
                        ->orWhere('details->detail', 'like', "%{$search}%");
                });
            })
            ->when($module !== 'all', function (Builder $query) use ($module): void {
                $query->where('details->module', $module);
            })
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $allLogs = SystemLog::query()->get();

        $logStats = [
            'success' => $allLogs->filter(fn (SystemLog $log): bool => ($log->details['status'] ?? 'success') === 'success')->count(),
            'error' => $allLogs->filter(fn (SystemLog $log): bool => ($log->details['status'] ?? null) === 'error')->count(),
            'warning' => $allLogs->filter(fn (SystemLog $log): bool => ($log->details['status'] ?? null) === 'warning')->count(),
            'info' => $allLogs->filter(fn (SystemLog $log): bool => ($log->details['status'] ?? null) === 'info')->count(),
        ];

        return Inertia::render('admin/administrator/index', [
            'logs' => $logs,
            'logStats' => $logStats,
            'logFilters' => ['search' => $search, 'module' => $module],
            'adminUsers' => User::query()->where('role', 'admin')->orderBy('first_name')->get(),
            'services' => Service::query()->orderBy('name')->get(),
            'profile' => $request->user(),
        ]);
    }
}
