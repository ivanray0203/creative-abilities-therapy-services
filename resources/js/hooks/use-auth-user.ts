import { usePage } from '@inertiajs/react';

import type { Auth, User } from '@/types/auth';

/**
 * Only use inside routes guarded by the `auth` middleware — those pages
 * are guaranteed a logged-in user by the server, so this asserts non-null
 * rather than forcing every consumer to re-check.
 */
export function useAuthUser(): User {
    const { auth } = usePage<{ auth: Auth }>().props;

    if (!auth.user) {
        throw new Error(
            'useAuthUser() was called outside an authenticated route.',
        );
    }

    return auth.user;
}
