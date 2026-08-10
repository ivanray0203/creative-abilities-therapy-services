/**
 * Whether a sidebar item points at the page currently open.
 *
 * `usePage().url` carries the query string and hash, so a plain equality
 * check drops the highlight the moment a list is searched, filtered or
 * paged — `/admin/clients?page=2` matches neither `/admin/clients` nor
 * `/admin/clients/`. Comparing paths alone keeps the item lit.
 *
 * Sub-pages count as the section they belong to (`/admin/clients/3/edit`
 * lights "Clients"), except for a role's root — `/admin` would otherwise
 * light "Dashboard" everywhere.
 */
export function isActiveNavItem(
    currentUrl: string,
    itemUrl: string,
    { exact = false }: { exact?: boolean } = {},
): boolean {
    const path = currentUrl.split(/[?#]/)[0].replace(/\/+$/, '') || '/';
    const target = itemUrl.replace(/\/+$/, '') || '/';

    if (exact) {
        return path === target;
    }

    return path === target || path.startsWith(`${target}/`);
}
