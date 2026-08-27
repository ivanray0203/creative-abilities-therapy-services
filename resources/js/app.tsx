import { createInertiaApp } from '@inertiajs/react';
import type { ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'CATS';

/**
 * No `setup` callback here on purpose: the `@inertiajs/vite` plugin
 * (registered in vite.config.ts) mounts/hydrates the app automatically for
 * both client and SSR requests. A custom `setup` that calls `createRoot`
 * unconditionally breaks SSR, since `createRoot` is a browser-only API and
 * there's no real DOM element to attach to when rendering on the server.
 */
createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob<ResolvedComponent>('./pages/**/*.tsx'),
        ),
    progress: {
        color: '#D87E45',
    },
});
