import { Link, usePage } from '@inertiajs/react';
import { AlertCircle, Calendar, DollarSign, LogOut, User } from 'lucide-react';

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
    { title: 'Calendar', icon: Calendar, url: '/client/calendar' },
    { title: 'Invoices', icon: DollarSign, url: '/client/invoices' },
    { title: 'Complaints', icon: AlertCircle, url: '/client/complaints' },
    { title: 'Profile', icon: User, url: '/client/profile' },
];

export function ClientSidebar({ onLogout }: { onLogout: () => void }) {
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
                                const isSelected =
                                    currentUrl === item.url ||
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
