import { Badge } from '@/components/ui/badge';
import type { InvoiceStatus } from '@/types/invoice';

/** Invoice status badge, mirrors admin/intake/badges.tsx conventions. */

const STATUS_LABELS: Record<InvoiceStatus, string> = {
    draft: 'Draft',
    sent: 'Sent',
    paid: 'Paid',
    overdue: 'Overdue',
    unpaid: 'Unpaid',
    refunded: 'Refunded',
};

const STATUS_CLASSES: Record<InvoiceStatus, string> = {
    draft: 'bg-gray-100 text-gray-700 border border-gray-400',
    sent: 'bg-blue-100 text-blue-700 border border-blue-400',
    paid: 'bg-green-100 text-green-700 border border-green-400',
    overdue: 'bg-red-100 text-red-700 border border-red-400',
    unpaid: 'bg-yellow-100 text-yellow-800 border border-yellow-400',
    refunded: 'bg-purple-100 text-purple-700 border border-purple-400',
};

export function InvoiceStatusBadge({ status }: { status: InvoiceStatus }) {
    return (
        <Badge
            className={`rounded-[5px] text-xs md:text-sm ${STATUS_CLASSES[status]}`}
        >
            {STATUS_LABELS[status]}
        </Badge>
    );
}
