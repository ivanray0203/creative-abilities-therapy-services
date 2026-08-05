import InvoicesTable from '@/components/invoices/invoices-table';
import { Card, CardContent } from '@/components/ui/card';
import type { Client } from '@/types/client';

/** Reference: cats-frontend/src/pages/admin/clientTabs/Invoices.tsx */
export default function InvoicesTab({ client }: { client: Client }) {
    return (
        <div className="grid grid-cols-1 gap-2 pt-2 md:p-5 md:pt-0">
            <Card className="rounded-[10px]">
                <CardContent className="p-5">
                    <InvoicesTable
                        invoices={client.invoices ?? []}
                        basePath="/admin/invoices"
                    />
                </CardContent>
            </Card>
        </div>
    );
}
