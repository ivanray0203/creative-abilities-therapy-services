import type { ScheduleSession } from '@/types/session';

type SessionServiceFields = Pick<
    ScheduleSession,
    'client_services' | 'service' | 'service_name'
>;

/**
 * Every availed service a visit covers, one entry each.
 *
 * A session can deliver several of a child's services at once, so the single
 * `service` relation only names the first of them. Sessions booked without
 * any availed service attached (and older ones from before the pivot) fall
 * back to that relation, then to the free-text `service_name`.
 */
export function sessionServiceNames(session: SessionServiceFields): string[] {
    const covered = (session.client_services ?? [])
        .map((clientService) => clientService.service?.name)
        .filter((name): name is string => Boolean(name));

    if (covered.length > 0) {
        return covered;
    }

    const single = session.service?.name || session.service_name;

    return single ? [single] : [];
}

/** The same services as one line of text, for tables, CSVs and option labels. */
export function sessionServiceLabel(
    session: SessionServiceFields,
    fallback = 'Service',
): string {
    const names = sessionServiceNames(session);

    return names.length > 0 ? names.join(', ') : fallback;
}
