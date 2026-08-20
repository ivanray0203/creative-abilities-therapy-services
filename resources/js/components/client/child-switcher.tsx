import { router, usePage } from '@inertiajs/react';
import { Baby } from 'lucide-react';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Auth } from '@/types/auth';

/**
 * Lets a parent with several children in care choose which one the portal is
 * scoped to (Phase 17). Every client page — calendar, invoices, complaints,
 * profile — follows this selection.
 *
 * Renders nothing for a parent with a single child, so the common case keeps
 * the header uncluttered.
 */
export function ChildSwitcher() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const children = auth.children ?? [];

    if (children.length < 2) {
        return null;
    }

    const handleChange = (value: string) => {
        router.post(
            '/client/select-child',
            { client_id: Number(value) },
            { preserveScroll: true },
        );
    };

    return (
        <div className="flex items-center gap-2">
            <Baby className="h-4 w-4 shrink-0 text-muted-foreground" />
            <Select
                value={auth.client_id ? String(auth.client_id) : undefined}
                onValueChange={handleChange}
            >
                <SelectTrigger
                    id="child-switcher"
                    aria-label="Select which child to view"
                    className="w-[180px] rounded-[10px]"
                >
                    <SelectValue placeholder="Select child..." />
                </SelectTrigger>
                <SelectContent>
                    {children.map((child) => (
                        <SelectItem key={child.id} value={String(child.id)}>
                            {child.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
