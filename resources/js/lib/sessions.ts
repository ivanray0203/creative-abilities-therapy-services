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

export interface SessionServiceRef {
    /** Null for a session that only carries the free-text `service_name`. */
    id: number | null;
    name: string;
}

/**
 * The same services as {@link sessionServiceNames}, but keeping each one's
 * offering id so a caller can match it back to a service dropdown and pull
 * the configured rate. Used to seed invoice lines from a linked session.
 */
export function sessionServiceRefs(
    session: SessionServiceFields,
): SessionServiceRef[] {
    const covered = (session.client_services ?? [])
        .map((clientService) => clientService.service)
        .filter((service) => Boolean(service?.name))
        .map((service) => ({ id: service!.id, name: service!.name }));

    if (covered.length > 0) {
        return covered;
    }

    if (session.service?.name) {
        return [{ id: session.service.id, name: session.service.name }];
    }

    return session.service_name
        ? [{ id: null, name: session.service_name }]
        : [];
}

/** The same services as one line of text, for tables, CSVs and option labels. */
export function sessionServiceLabel(
    session: SessionServiceFields,
    fallback = 'Service',
): string {
    const names = sessionServiceNames(session);

    return names.length > 0 ? names.join(', ') : fallback;
}
