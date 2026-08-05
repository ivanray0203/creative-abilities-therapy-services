/** Matches `SystemLog` (app/Models/SystemLog.php). Populated starting Phase 16. */

export interface SystemLogUser {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

export interface SystemLogDetails {
    status?: 'success' | 'error' | 'warning' | 'info';
    user_email?: string;
    module?: string;
    detail?: string;
}

export interface SystemLog {
    id: number;
    user_id: number | null;
    action: string;
    details: SystemLogDetails | null;
    ip_address: string | null;
    created_at: string;
    updated_at: string;
    user?: SystemLogUser | null;
}

export interface LogStats {
    success: number;
    error: number;
    warning: number;
    info: number;
}

export type { Paginated } from '@/types/intake';
