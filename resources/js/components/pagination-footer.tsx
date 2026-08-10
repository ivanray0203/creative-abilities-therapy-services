import { Button } from '@/components/ui/button';

/**
 * The "Showing 1–15 of 42" line plus page controls that sits under a
 * paginated list.
 *
 * Several lists paginate server-side but rendered no controls, stranding
 * everything past the first page. Keeping the markup here means a list only
 * has to pass its paginator through.
 */
export default function PaginationFooter({
    currentPage,
    lastPage,
    perPage,
    total,
    countOnPage,
    onPageChange,
    className = '',
}: {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    /** Rows actually rendered — the last page is usually short. */
    countOnPage: number;
    onPageChange: (page: number) => void;
    className?: string;
}) {
    if (total === 0) {
        return null;
    }

    const first = (currentPage - 1) * perPage + 1;
    const last = first + countOnPage - 1;

    return (
        <div
            className={`flex flex-col items-center justify-between gap-3 sm:flex-row ${className}`}
        >
            <p className="text-sm text-muted-foreground">
                Showing {first}–{last} of {total}
            </p>

            {lastPage > 1 && (
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        className="rounded-[10px]"
                        onClick={() => onPageChange(currentPage - 1)}
                        disabled={currentPage <= 1}
                    >
                        Previous
                    </Button>
                    <span className="px-2 text-sm">
                        Page {currentPage} of {lastPage}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        className="rounded-[10px]"
                        onClick={() => onPageChange(currentPage + 1)}
                        disabled={currentPage >= lastPage}
                    >
                        Next
                    </Button>
                </div>
            )}
        </div>
    );
}
