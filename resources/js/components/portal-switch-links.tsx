import { Link, usePage } from '@inertiajs/react';
import { ArrowLeftRight } from 'lucide-react';

import {
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import type { Auth } from '@/types/auth';

/*
 * Someone who holds more than one role — an admin who is also a therapist —
 * moves between their portals from here. Renders nothing for the usual
 * single-role user.
 */
export function PortalSwitchLinks() {
    const { open } = useSidebar();
    const { portals } = usePage<{ auth: Auth }>().props.auth;

    return portals.map((portal) => (
        <SidebarMenuItem
            key={portal.role}
            className="mt-2 border-t border-sidebar-border pt-2"
        >
            <SidebarMenuButton asChild>
                <Link
                    href={portal.url}
                    className="flex items-center gap-2 rounded-[5px] px-3 py-2 text-sidebar-foreground hover:bg-secondary-orange/20"
                >
                    <ArrowLeftRight className="h-4 w-4" />
                    {open && (
                        <span>
                            Switch to{' '}
                            <span className="capitalize">{portal.role}</span>{' '}
                            portal
                        </span>
                    )}
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    ));
}
