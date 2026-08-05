<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Writes `system_logs` rows consumed by the admin "System Logs" tab
 * (resources/js/components/admin/administrator/system-logs-tab.tsx).
 *
 * The Django reference has no backend equivalent — `LogAction()` there is a
 * frontend-only helper with no Python call sites to port from — so this
 * shape is driven entirely by what the already-ported admin UI expects:
 * `details = {status, user_email, module, detail}` plus `ip_address`.
 *
 * `module` should be one of the values the System Logs module filter
 * dropdown offers: Authentication, Clients, Services, Invoices, Users,
 * Intake, Applications, System (catch-all for everything else).
 */
class AuditLogger
{
    public static function log(
        string $action,
        string $module,
        string $detail,
        string $status = 'success',
        ?string $userEmail = null,
    ): void {
        $user = Auth::user();
        $resolvedEmail = $userEmail ?? ($user !== null ? $user->email : null) ?? 'system';

        SystemLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'details' => [
                'status' => $status,
                'user_email' => $resolvedEmail,
                'module' => $module,
                'detail' => $detail,
            ],
            'ip_address' => Request::ip(),
        ]);
    }
}
