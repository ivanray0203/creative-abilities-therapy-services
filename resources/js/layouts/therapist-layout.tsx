import { router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import type { PropsWithChildren } from 'react';

import FlashToaster from '@/components/flash-toaster';
import ActiveSessionCard from '@/components/therapist/active-session-card';
import { TherapistSidebar } from '@/components/therapist-sidebar';
import { Button } from '@/components/ui/button';
import { SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { useAuthUser } from '@/hooks/use-auth-user';

export default function TherapistLayout({ children }: PropsWithChildren) {
    const user = useAuthUser();
    const { activeSession } = usePage().props;

    const handleLogout = () => router.post('/logout');

    return (
        <SidebarProvider>
            <FlashToaster />
            <div className="flex min-h-screen w-full">
                <TherapistSidebar onLogout={handleLogout} />
                <div className="flex flex-1 flex-col">
                    <header className="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-border bg-background px-6">
                        <div className="flex items-center gap-4">
                            <SidebarTrigger />
                            <div>
                                <h1 className="text-lg font-semibold text-primary">
                                    CATS Therapist
                                </h1>
                                <p className="text-xs text-muted-foreground">
                                    Creative Abilities Therapy Services
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <div className="hidden items-center gap-1 border-l p-2 md:flex md:flex-col md:items-start">
                                <span className="text-sm font-medium">
                                    {user.first_name || user.email}{' '}
                                    {user.last_name}
                                </span>
                                <span className="text-xs text-muted-foreground capitalize">
                                    {user.role.toLowerCase()}
                                </span>
                            </div>
                            <Button
                                variant="outline"
                                size="icon"
                                onClick={handleLogout}
                                className="hidden rounded border border-red-700 text-red-600 hover:bg-red-200 hover:text-red-700 md:flex"
                            >
                                <LogOut className="h-4 w-4" />
                            </Button>
                        </div>
                    </header>
                    <main className="flex-1 bg-secondary-orange/5">
                        {activeSession && (
                            <div className="p-6 pb-0">
                                <ActiveSessionCard
                                    activeSession={activeSession}
                                />
                            </div>
                        )}
                        {children}
                    </main>
                </div>
            </div>
        </SidebarProvider>
    );
}
