/** Turns `under_review` into `Under Review` (reference: lib/helpers.tsx `capitalize`). */
export function capitalize(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

/** Relative time like "2 hours ago" (reference: lib/helpers.tsx `timeAgo`). */
export function timeAgo(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const seconds = Math.floor((Date.now() - new Date(value).getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const units: [number, string][] = [
        [31536000, 'year'],
        [2592000, 'month'],
        [86400, 'day'],
        [3600, 'hour'],
        [60, 'minute'],
    ];

    for (const [unitSeconds, label] of units) {
        const count = Math.floor(seconds / unitSeconds);

        if (count >= 1) {
            return `${count} ${label}${count > 1 ? 's' : ''} ago`;
        }
    }

    return 'just now';
}

export function getInitials(name: string): string {
    const parts = name.trim().split(' ');

    return parts
        .map((p) => p[0])
        .join('')
        .toUpperCase();
}

/**
 * Formats a date-only value (e.g. invoice date/due date) into a readable
 * form like "Aug 6, 2026". Laravel's `date` cast still serializes to JSON as
 * a full `YYYY-MM-DDTHH:mm:ss.uuuuuuZ` string (just with the time zeroed
 * out), so the date portion is sliced off first, then parsed manually and
 * built from local components rather than `new Date(value)` — passing the
 * full string straight through would correctly parse as UTC midnight, but
 * formatting it can still roll back to the previous day in a
 * negative-UTC-offset browser timezone.
 */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    if (!year || !month || !day) {
        return value;
    }

    return new Date(year, month - 1, day).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

/**
 * `scheduled_start`/`scheduled_end` on sessions are wall-clock times for the
 * clinic's single timezone, serialized with a `Z` suffix because
 * `config('app.timezone')` is UTC — they are NOT true UTC instants meant for
 * per-viewer timezone conversion. Reading them with plain local-time methods
 * (`toLocaleTimeString()`, `date-fns` `format`/`isSameDay`, etc.) silently
 * shifts the displayed clock by the viewer's own browser offset. Always go
 * through these helpers instead, so every viewer sees the same clock time.
 */
export function formatScheduledTime(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'UTC',
    });
}

export function formatScheduledDate(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleDateString([], { timeZone: 'UTC' });
}

export function formatScheduledDateTime(
    value: string | null | undefined,
): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString([], { timeZone: 'UTC' });
}

/**
 * Reinterprets a wall-clock (UTC-labeled) session timestamp as a Date whose
 * LOCAL getters return those same clock-face numbers — for feeding into
 * libraries (e.g. date-fns's `isSameDay`/`format`) that only read local time
 * and don't accept a `timeZone` option.
 */
export function toScheduledDisplayDate(value: string): Date {
    const parsed = new Date(value);

    return new Date(
        parsed.getUTCFullYear(),
        parsed.getUTCMonth(),
        parsed.getUTCDate(),
        parsed.getUTCHours(),
        parsed.getUTCMinutes(),
        parsed.getUTCSeconds(),
    );
}
