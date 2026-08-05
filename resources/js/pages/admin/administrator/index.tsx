import { Head } from '@inertiajs/react';
import { Monitor } from 'lucide-react';

import AdminUsersTab from '@/components/admin/administrator/admin-users-tab';
import ServicesTab from '@/components/admin/administrator/services-tab';
import SettingsTab from '@/components/admin/administrator/settings-tab';
import SystemLogsTab from '@/components/admin/administrator/system-logs-tab';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin-layout';
import type { AdministratorPageProps } from '@/types/administrator';

/**
 * Admin "Administrator" panel, ported from
 * cats-frontend/src/pages/admin/AdministratorPage.tsx — desktop-only,
 * blocked on mobile via the reference's exact Tailwind pattern (no JS
 * hook, avoids a hydration flash).
 */
export default function AdminAdministratorIndex({
    logs,
    logStats,
    logFilters,
    adminUsers,
    services,
    profile,
}: AdministratorPageProps) {
    return (
        <>
            <Head title="Administrator" />

            <div className="block flex min-h-screen items-center justify-center bg-gray-50 p-6 md:hidden">
                <div className="flex flex-col items-center gap-3 text-center">
                    <Monitor className="h-10 w-10 text-muted-foreground" />
                    <p className="text-lg font-medium">Administrator View</p>
                    <p className="text-sm text-muted-foreground">
                        This section is available only on larger screens. Please
                        use a desktop or tablet device.
                    </p>
                </div>
            </div>

            <div className="hidden space-y-6 p-6 md:block">
                <div>
                    <h1 className="text-2xl font-bold sm:text-3xl">
                        Administrator
                    </h1>
                    <p className="text-sm text-muted-foreground sm:text-base">
                        System logs, admin users, services, and account settings
                    </p>
                </div>

                <Tabs defaultValue="logs">
                    <TabsList>
                        <TabsTrigger value="logs">System Logs</TabsTrigger>
                        <TabsTrigger value="users">Admin Users</TabsTrigger>
                        <TabsTrigger value="services">Services</TabsTrigger>
                        <TabsTrigger value="settings">Settings</TabsTrigger>
                    </TabsList>

                    <TabsContent value="logs" className="mt-4">
                        <SystemLogsTab
                            logs={logs}
                            logStats={logStats}
                            filters={logFilters}
                        />
                    </TabsContent>
                    <TabsContent value="users" className="mt-4">
                        <AdminUsersTab adminUsers={adminUsers} />
                    </TabsContent>
                    <TabsContent value="services" className="mt-4">
                        <ServicesTab services={services} />
                    </TabsContent>
                    <TabsContent value="settings" className="mt-4">
                        <SettingsTab profile={profile} />
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}

AdminAdministratorIndex.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
