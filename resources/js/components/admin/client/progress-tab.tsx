import { TrendingUp } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';

/** Reference: cats-frontend/src/pages/admin/clientTabs/Progress.tsx — permanent stub, do not build out. */
export default function ProgressTab() {
    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
            <Card className="rounded-[10px]">
                <CardContent className="flex flex-col items-center gap-3 p-10 text-center text-muted-foreground">
                    <TrendingUp className="h-8 w-8" />
                    <p>Progress — Coming Soon</p>
                </CardContent>
            </Card>
        </div>
    );
}
