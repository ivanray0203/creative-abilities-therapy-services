import { Check, ChevronDown } from 'lucide-react';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export interface ComboboxOption {
    value: string;
    label: string;
    /** Matched by the search box alongside the label, but not displayed. */
    keywords?: string;
}

/**
 * A Select you can type into, for fields whose option list is too long to
 * scan — the invoice rate card runs to well over a hundred lines.
 *
 * Wears SelectTrigger's clothes so it sits beside the plain Selects around
 * it, and pins its list below the field rather than letting it flip above.
 */
export function Combobox({
    id,
    options,
    value,
    onChange,
    placeholder = 'Select option',
    searchPlaceholder = 'Search...',
    emptyLabel = 'No matches found.',
    className,
    contentClassName,
    disabled,
}: {
    id?: string;
    options: ComboboxOption[];
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyLabel?: string;
    className?: string;
    contentClassName?: string;
    disabled?: boolean;
}) {
    const [open, setOpen] = React.useState(false);
    const selected = options.find((option) => option.value === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    disabled={disabled}
                    className={cn(
                        'h-10 w-full justify-between border-input px-3 py-2 font-normal',
                        !selected && 'text-muted-foreground',
                        className,
                    )}
                >
                    <span className="truncate">
                        {selected?.label ?? placeholder}
                    </span>
                    <ChevronDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="start"
                side="bottom"
                avoidCollisions={false}
                className={cn(
                    'w-[var(--radix-popover-trigger-width)] p-0',
                    contentClassName,
                )}
            >
                <Command>
                    <CommandInput placeholder={searchPlaceholder} />
                    <CommandList className="max-h-[min(18rem,var(--radix-popover-content-available-height))]">
                        <CommandEmpty>{emptyLabel}</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => (
                                <CommandItem
                                    key={option.value}
                                    // cmdk searches this, not the rendered
                                    // node, so the label has to be in it.
                                    value={`${option.label} ${option.keywords ?? ''}`}
                                    onSelect={() => {
                                        onChange(option.value);
                                        setOpen(false);
                                    }}
                                >
                                    <Check
                                        className={cn(
                                            'mr-2 h-4 w-4 shrink-0',
                                            option.value === value
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                    <span>{option.label}</span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
