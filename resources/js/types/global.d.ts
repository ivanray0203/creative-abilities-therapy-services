import type { Auth } from '@/types/auth';
import type { ScheduleSession } from '@/types/session';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            activeSession: ScheduleSession | null;
            flash: {
                success: string | null;
                error: string | null;
            };
            organization: {
                name: string;
                phone: string | null;
                email: string | null;
                address: string | null;
            };
            [key: string]: unknown;
        };
    }
}
