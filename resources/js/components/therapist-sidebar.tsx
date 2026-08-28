import { Link, usePage } from '@inertiajs/react';
import {
    Calendar,
    ClipboardIcon,
    ClipboardList,
    Clock,
    DollarSign,
    FileText,
    LayoutDashboard,
    LogOut,
    NotebookPen,
    Receipt,
    User,
    Users,
} from 'lucide-react';

import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useAuthUser } from '@/hooks/use-auth-user';
import { isAidePosition } from '@/lib/aide';
import { getInitials } from '@/lib/helpers';
import { isActiveNavItem } from '@/lib/navigation';
import type { Auth } from '@/types/auth';

/*
 * The therapist portal splits down the middle at the billing step. A
 * therapist bills services and raises invoices; an aide logs hours and
 * generates the time sheet the parent signs. Everything either side of
 * those two entries is the same, so only the pair swaps.
 */
const BILLING_ITEMS = [
    { title: 'Billing', icon: DollarSign, url: '/therapist/billing' },
    { title: 'Invoices', icon: Receipt, url: '/therapist/invoices' },
];

const AIDE_ITEMS = [
    { title: 'Hours', icon: Clock, url: '/therapist/hours' },
    { title: 'Timesheets', icon: NotebookPen, url: '/therapist/timesheets' },
];

const menuItemsFor = (isAide: boolean) => [
    { title: 'Dashboard', icon: LayoutDashboard, url: '/therapist' },
    { title: 'Calendar', icon: Calendar, url: '/therapist/calendar' },
    { title: 'Sessions', icon: ClipboardIcon, url: '/therapist/sessions' },
    { title: 'Clients', icon: Users, url: '/therapist/clients' },
    { title: 'Reviews', icon: ClipboardList, url: '/therapist/intake' },
    ...(isAide ? AIDE_ITEMS : BILLING_ITEMS),
    { title: 'Complaints', icon: FileText, url: '/therapist/complaints' },
    { title: 'Profile', icon: User, url: '/therapist/profile' },
];

export function TherapistSidebar({ onLogout }: { onLogout: () => void }) {
    const { open } = useSidebar();
    const user = useAuthUser();
    const page = usePage<{ auth: Auth }>();
    const currentUrl = page.url;
    const menuItems = menuItemsFor(
        isAidePosition(page.props.auth.team_member?.position),
    );

    return (
        <Sidebar className="border-r border-sidebar-border">
            <SidebarContent>
                <div className="border-b border-sidebar-border p-4">
                    <div className="flex items-center gap-3">
                        <Avatar className="h-10 w-10 bg-primary">
                            <AvatarFallback className="bg-primary text-primary-foreground">
                                {getInitials(
                                    `${user.first_name} ${user.last_name}`,
                                )}
                            </AvatarFallback>
                        </Avatar>
                        {open && (
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium text-sidebar-foreground">
                                    {user.first_name} {user.last_name}
                                </p>
                                <p className="truncate text-xs text-muted-foreground">
                                    {user.email}
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {menuItems.map((item) => {
                                const isSelected = isActiveNavItem(
                                    currentUrl,
                                    item.url,
                                    { exact: item.url === '/therapist' },
                                );

                                return (
                                    <SidebarMenuItem key={item.title}>
                                        <SidebarMenuButton asChild>
                                            <Link
                                                href={item.url}
                                                className={`flex items-center gap-2 rounded-[5px] px-3 py-2 ${
                                                    isSelected
                                                        ? 'bg-secondary-orange/10 font-medium text-primary'
                                                        : 'text-sidebar-foreground hover:bg-secondary-orange/20'
                                                }`}
                                            >
                                                <item.icon className="h-4 w-4" />
                                                {open && (
                                                    <span>{item.title}</span>
                                                )}
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                );
                            })}
                            <SidebarMenuItem className="mt-2 block sm:hidden">
                                <SidebarMenuButton asChild>
                                    <button
                                        onClick={onLogout}
                                        className="flex w-full items-center gap-2 rounded-[5px] px-3 py-2 text-red-600 hover:bg-red-100"
                                    >
                                        <LogOut className="h-4 w-4" />
                                        Logout
                                    </button>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>
        </Sidebar>
    );
}
