import { router } from '@inertiajs/react';
import { AlertCircleIcon } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import type { AdminService } from '@/types/administrator';

/** Reference: cats-frontend/src/pages/admin/administratorTabs/ServicesTab.tsx */
export default function ServicesTab({
    services,
}: {
    services: AdminService[];
}) {
    const toggle = (service: AdminService, isActive: boolean) => {
        router.patch(
            `/admin/administrator/services/${service.id}`,
            { is_active: isActive },
            { preserveScroll: true },
        );
    };

    return (
        <div className="grid grid-cols-1 gap-5">
            <div className="flex items-start gap-3 rounded-[5px] border border-blue-400 bg-blue-50 p-4 text-sm text-blue-800">
                <AlertCircleIcon className="h-5 w-5 shrink-0" />
                Hidden services will not appear in the Services page but will
                remain accessible in the admin portal.
            </div>

            <div className="grid grid-cols-1 gap-3">
                {services.map((service) => (
                    <Card key={service.id} className="rounded-[10px]">
                        <CardContent className="flex items-center justify-between p-5">
                            <div>
                                <p className="font-medium">{service.name}</p>
                                <p className="text-sm text-muted-foreground">
                                    {service.short_description}
                                </p>
                            </div>
                            <Switch
                                checked={service.is_active}
                                onCheckedChange={(checked) =>
                                    toggle(service, checked)
                                }
                            />
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}
