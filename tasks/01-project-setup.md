# Phase 1 — Project Setup & Foundation

## Goal
Stand up the Laravel + Inertia + React skeleton that every later phase builds on, and port the reference's visual design system so pages look identical from the start.

## Tasks

### 1.1 Laravel/Inertia scaffolding
- Confirm `cats-final` already has: Laravel 13, Inertia v3 (`inertiajs/inertia-laravel`), React 19, Tailwind v4, Wayfinder, Pint, Pest — verify via `composer.json`/`package.json` (already present per repo listing).
- Set up `resources/js/pages` directory structure mirroring the reference's role split: `pages/public/`, `pages/admin/`, `pages/therapist/`, `pages/client/`, `pages/auth/`.
- Configure `vite.config.ts` aliases (`@/*`) to match reference's `src/*` import style.

### 1.2 Design system port
- Port shadcn/ui components used in the reference (`src/components/ui/*`) into `resources/js/components/ui/*`: accordion, alert, alert-dialog, avatar, badge, breadcrumb, button, calendar, card, carousel, chart, checkbox, collapsible, command, dialog, drawer, dropdown-menu, form, hover-card, input, input-otp, label, menubar, navigation-menu, pagination, popover, progress, radio-group, resizable, scroll-area, select, separator, sheet, sidebar, skeleton, slider, sonner/toast, switch, table, tabs, textarea, toggle/toggle-group, tooltip.
- Copy Tailwind theme tokens (colors, spacing, fonts) from reference `tailwind.config`/CSS variables so visuals match exactly.
- Port static assets: `public/CatsLogo`, `public/images`, `src/assets/images`.

### 1.3 Base layouts
- `AdminLayout` (sidebar: Intake, Clients, Sessions, Services, Invoices, Applications, Team, Complaints & Disputes, Administrator — matches reference's actual visible nav, not the commented-out items).
- `TherapistLayout` (sidebar matches reference nav; wraps active-session state, see Phase 13).
- `ClientLayout` (sidebar: Calendar, Invoices, Complaints, Profile).
- Public `Header`/`Footer` layout for marketing pages.
- Shared `ProtectedRoute`-equivalent: Laravel middleware `EnsureRole:admin|therapist|client` applied per route group, redirecting unauthorized users to their own role home (mirrors reference's role-mismatch redirect behavior).

### 1.4 Dev tooling
- Confirm ESLint/Prettier configs match reference conventions.
- `composer run dev` / `npm run dev` verified working end-to-end with a placeholder Inertia page.

## Acceptance criteria
- Fresh `php artisan serve` + `npm run dev` renders a placeholder page through Inertia using at least 3 ported UI components, visually matching the reference's look and feel (fonts, colors, spacing).
- Directory structure agreed upon and documented in this repo's own CLAUDE.md/AGENTS.md if conventions diverge from Boost defaults.
