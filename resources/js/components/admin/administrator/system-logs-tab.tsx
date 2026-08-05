import { router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { exportSystemLogsCsv } from '@/lib/system-logs-csv';
import type { LogStats, Paginated, SystemLog } from '@/types/system-log';

const MODULES = [
    'Authentication',
    'Clients',
    'Services',
    'Invoices',
    'Users',
    'Intake',
    'Applications',
    'System',
];

const STATUS_CLASSES: Record<string, string> = {
    success: 'bg-green-100 text-green-700 border border-green-400',
    error: 'bg-red-100 text-red-700 border border-red-400',
    warning: 'bg-yellow-100 text-yellow-800 border border-yellow-400',
    info: 'bg-blue-100 text-blue-700 border border-blue-400',
};

/** Reference: cats-frontend/src/pages/admin/administratorTabs/SystemLogsTab.tsx */
export default function SystemLogsTab({
    logs,
    logStats,
    filters,
}: {
    logs: Paginated<SystemLog>;
    logStats: LogStats;
    filters: { search: string; module: string };
}) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                '/admin/administrator',
                { search, module: filters.module },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const applyModule = (module: string) => {
        router.get(
            '/admin/administrator',
            { search, module },
            { preserveState: true, replace: true },
        );
    };

    return (
        <div className="grid grid-cols-1 gap-5">
            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">Success</p>
                    <p className="text-2xl font-bold text-green-600">
                        {logStats.success}
                    </p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">Error</p>
                    <p className="text-2xl font-bold text-red-600">
                        {logStats.error}
                    </p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">Warning</p>
                    <p className="text-2xl font-bold text-yellow-600">
                        {logStats.warning}
                    </p>
                </Card>
                <Card className="p-4">
                    <p className="text-xs text-muted-foreground">Info</p>
                    <p className="text-2xl font-bold text-blue-600">
                        {logStats.info}
                    </p>
                </Card>
            </div>

            <Card className="rounded-[10px] p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div className="flex-1">
                        <Input
                            placeholder="Search by user, action, or detail..."
                            className="rounded-[10px]"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                    <Select value={filters.module} onValueChange={applyModule}>
                        <SelectTrigger className="rounded-[10px] sm:w-48">
                            <SelectValue placeholder="Module" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Modules</SelectItem>
                            {MODULES.map((module) => (
                                <SelectItem key={module} value={module}>
                                    {module}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button
                        variant="outline"
                        className="rounded-[10px]"
                        onClick={() => exportSystemLogsCsv(logs.data)}
                    >
                        <Download /> Export CSV
                    </Button>
                </div>
            </Card>

            <Card className="p-6">
                {logs.data.length > 0 ? (
                    <div className="w-full overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b text-left text-sm text-muted-foreground">
                                <tr>
                                    <th className="pb-3">Status</th>
                                    <th className="pb-3">Timestamp</th>
                                    <th className="pb-3">User</th>
                                    <th className="pb-3">Action</th>
                                    <th className="pb-3">Module</th>
                                    <th className="pb-3">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                {logs.data.map((log) => (
                                    <tr
                                        key={log.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="py-3">
                                            <Badge
                                                className={`rounded-[5px] ${STATUS_CLASSES[log.details?.status ?? 'success']}`}
                                            >
                                                {log.details?.status ??
                                                    'success'}
                                            </Badge>
                                        </td>
                                        <td className="py-3">
                                            {new Date(
                                                log.created_at,
                                            ).toLocaleString()}
                                        </td>
                                        <td className="py-3">
                                            {log.details?.user_email ??
                                                log.user?.email ??
                                                '-'}
                                        </td>
                                        <td className="py-3">{log.action}</td>
                                        <td className="py-3">
                                            {log.details?.module ?? '-'}
                                        </td>
                                        <td className="py-3">
                                            {log.details?.detail ?? '-'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <p className="py-8 text-center text-muted-foreground">
                        No logs to show
                    </p>
                )}
            </Card>
        </div>
    );
}
