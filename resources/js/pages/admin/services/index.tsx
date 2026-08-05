import { Head, Link, router } from '@inertiajs/react';
import { Edit, Layers, Plus } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import type { ServiceOffering } from '@/types/client';
import type { Paginated } from '@/types/intake';

type ServiceType =
    'general_service' | 'specific_service' | 'non_direct_service';

const TABS: { key: ServiceType; label: string }[] = [
    { key: 'general_service', label: 'General Services' },
    { key: 'specific_service', label: 'Specific Services' },
    { key: 'non_direct_service', label: 'Non-Direct Services' },
];

interface AdminServicesIndexProps {
    services: Paginated<ServiceOffering>;
    stats: Record<ServiceType, number>;
    filters: { type: ServiceType };
}

/**
 * Admin "Service Offerings" catalog — reference: cats-frontend/src/pages/admin/Services.tsx.
 * Manages the internal ServiceOffering catalog used when scheduling
 * sessions/invoices (distinct from the public marketing Service model
 * managed under Administrator > Services visibility toggles).
 */
export default function AdminServicesIndex({
    services,
    stats,
    filters,
}: AdminServicesIndexProps) {
    const applyType = (type: ServiceType) => {
        router.get(
            '/admin/services',
            { type },
            { preserveState: true, replace: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            '/admin/services',
            { type: filters.type, page },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Services" />
            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-primary/10 p-3">
                            <Layers className="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold">
                                Service Offerings
                            </h1>
                            <p className="text-muted-foreground">
                                Manage services offered by your organization
                            </p>
                        </div>
                    </div>

                    <Button asChild className="rounded-[10px]">
                        <Link href="/admin/services/add">
                            <Plus className="mr-2 h-4 w-4" /> Add Service
                        </Link>
                    </Button>
                </div>

                {/* Tabs */}
                <div className="flex gap-4 border-b">
                    {TABS.map((tab) => (
                        <button
                            key={tab.key}
                            onClick={() => applyType(tab.key)}
                            className={`flex items-center gap-2 border-b-2 px-4 py-2 font-medium ${
                                filters.type === tab.key
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {tab.label}
                            <Badge variant="secondary">
                                {stats[tab.key]}
                            </Badge>
                        </button>
                    ))}
                </div>

                {/* Content */}
                <Card className="p-6">
                    {services.data.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No services found for this category.
                        </p>
                    )}

                    <div className="space-y-4">
                        {services.data.map((service) => (
                            <Card
                                key={service.id}
                                className="rounded-lg border p-5 transition hover:shadow-md"
                            >
                                <div className="flex items-start justify-between">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <h3 className="text-lg font-semibold">
                                                {service.name}
                                            </h3>
                                            {!service.is_active && (
                                                <Badge variant="destructive">
                                                    Inactive
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            Code: {service.code}
                                        </p>
                                    </div>

                                    <Button
                                        asChild
                                        size="sm"
                                        variant="outline"
                                        className="rounded-[10px]"
                                    >
                                        <Link
                                            href={`/admin/services/edit/${service.id}`}
                                        >
                                            <Edit className="mr-1 h-4 w-4" />{' '}
                                            Edit
                                        </Link>
                                    </Button>
                                </div>

                                {service.description && (
                                    <div className="mt-3 text-sm text-gray-700">
                                        {service.description}
                                    </div>
                                )}

                                <div className="mt-4 flex flex-wrap gap-4 text-sm">
                                    {service.base_price && (
                                        <div>
                                            <span className="text-muted-foreground">
                                                Base Price:
                                            </span>{' '}
                                            <strong>
                                                ${service.base_price}
                                            </strong>
                                        </div>
                                    )}

                                    <div>
                                        <span className="text-muted-foreground">
                                            Status:
                                        </span>{' '}
                                        {service.is_active
                                            ? 'Active'
                                            : 'Inactive'}
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>

                    {services.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-center gap-2">
                            <button
                                type="button"
                                className="rounded-[10px] border px-3 py-1.5 text-sm disabled:opacity-50"
                                onClick={() =>
                                    goToPage(services.current_page - 1)
                                }
                                disabled={services.current_page <= 1}
                            >
                                Previous
                            </button>
                            <span className="flex items-center px-2 text-sm">
                                Page {services.current_page} of{' '}
                                {services.last_page}
                            </span>
                            <button
                                type="button"
                                className="rounded-[10px] border px-3 py-1.5 text-sm disabled:opacity-50"
                                onClick={() =>
                                    goToPage(services.current_page + 1)
                                }
                                disabled={
                                    services.current_page >=
                                    services.last_page
                                }
                            >
                                Next
                            </button>
                        </div>
                    )}
                </Card>
            </div>
        </>
    );
}

AdminServicesIndex.layout = (page: React.ReactNode) => (
    <AdminLayout>{page}</AdminLayout>
);
