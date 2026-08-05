import type { LogStats, Paginated, SystemLog } from '@/types/system-log';

export interface AdminUser {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    role: string;
    is_active: boolean;
}

export interface AdminService {
    id: number;
    name: string;
    code: string;
    short_description: string | null;
    is_active: boolean;
}

export interface AdminProfile {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    new_intake: boolean;
    invoice_payments: boolean;
    session_reminders: boolean;
    new_applications: boolean;
}

export interface AdministratorPageProps {
    logs: Paginated<SystemLog>;
    logStats: LogStats;
    logFilters: { search: string; module: string };
    adminUsers: AdminUser[];
    services: AdminService[];
    profile: AdminProfile;
}
