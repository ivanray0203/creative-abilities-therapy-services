import { Head } from '@inertiajs/react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';

export default function AdminPlaceholder() {
    return (
        <>
            <Head title="Admin" />
            <div className="space-y-6 p-6">
                <div>
                    <h2 className="text-2xl font-semibold text-primary">Admin Dashboard</h2>
                    <p className="text-muted-foreground">Phase 1 scaffold — real Intake pipeline lands in Phase 6.</p>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Design system check</CardTitle>
                        <CardDescription>Confirms the ported shadcn/ui kit renders with CATS brand tokens.</CardDescription>
                    </CardHeader>
                    <CardContent className="flex items-center gap-4">
                        <Button>Primary action</Button>
                        <Button variant="outline">Secondary</Button>
                        <Badge>Active</Badge>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminPlaceholder.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
