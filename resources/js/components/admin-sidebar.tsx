import { Link, usePage } from '@inertiajs/react';
import {
    Briefcase,
    Calendar,
    Clipboard,
    ClipboardIcon,
    DollarSign,
    FileText,
    LayoutDashboard,
    LogOut,
    Megaphone,
    MessageSquare,
    Shield,
    UserCog,
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
import { getInitials } from '@/lib/helpers';

const menuItems = [
    { title: 'Dashboard', url: '/admin', icon: LayoutDashboard },
    { title: 'Calendar', url: '/admin/calendar', icon: Calendar },
    { title: 'Intake', url: '/admin/intake', icon: FileText },
    { title: 'Clients', url: '/admin/clients', icon: Users },
    { title: 'Sessions', url: '/admin/sessions', icon: ClipboardIcon },
    { title: 'Services', url: '/admin/services', icon: Briefcase },
    { title: 'Invoices', url: '/admin/invoices', icon: DollarSign },
    { title: 'Applications', url: '/admin/applications', icon: Clipboard },
    { title: 'Team', url: '/admin/team', icon: UserCog },
    { title: 'Positions', url: '/admin/careers', icon: Megaphone },
    {
        title: 'Complaints & Disputes',
        url: '/admin/messages',
        icon: MessageSquare,
    },
    { title: 'Administrator', url: '/admin/administrator', icon: Shield },
];

export function AdminSidebar({ onLogout }: { onLogout: () => void }) {
    const { open } = useSidebar();
    const user = useAuthUser();
    const currentUrl = usePage().url;

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
                                const isDashboard = item.url === '/admin';
                                const isSelected = isDashboard
                                    ? currentUrl === item.url
                                    : currentUrl === item.url ||
                                      currentUrl.startsWith(item.url + '/');

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
