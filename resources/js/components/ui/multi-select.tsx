import { ChevronDown } from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

export interface MultiSelectOption {
    value: number;
    label: string;
}

/**
 * A Select that takes more than one answer, for fields like the session
 * form's availed services. Radix's Select is single-value by design, so this
 * is a dropdown of checkbox items wearing SelectTrigger's clothes — picking
 * an option toggles it and leaves the menu open.
 */
export function MultiSelect({
    id,
    options,
    selected,
    onChange,
    placeholder = 'Select options',
    emptyLabel = 'No options available',
    className,
}: {
    id?: string;
    options: MultiSelectOption[];
    selected: number[];
    onChange: (selected: number[]) => void;
    placeholder?: string;
    emptyLabel?: string;
    className?: string;
}) {
    const chosen = options.filter((option) => selected.includes(option.value));
    const isEmpty = options.length === 0;

    const toggle = (value: number) => {
        onChange(
            selected.includes(value)
                ? selected.filter((current) => current !== value)
                : [...selected, value],
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                id={id}
                type="button"
                disabled={isEmpty}
                className={cn(
                    'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                    className,
                )}
            >
                <span
                    className={cn(
                        'line-clamp-1 text-left',
                        chosen.length === 0 && 'text-muted-foreground',
                    )}
                >
                    {isEmpty
                        ? emptyLabel
                        : chosen.length === 0
                          ? placeholder
                          : chosen.map((option) => option.label).join(', ')}
                </span>
                <ChevronDown className="h-4 w-4 shrink-0 opacity-50" />
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="start"
                className="max-h-64 w-[var(--radix-dropdown-menu-trigger-width)] overflow-y-auto"
            >
                {options.map((option) => (
                    <DropdownMenuCheckboxItem
                        key={option.value}
                        checked={selected.includes(option.value)}
                        // Keep the menu open so several can be picked at once.
                        onSelect={(event) => event.preventDefault()}
                        onCheckedChange={() => toggle(option.value)}
                    >
                        {option.label}
                    </DropdownMenuCheckboxItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
