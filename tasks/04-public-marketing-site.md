# Phase 4 — Public Marketing Site

## Goal
Port every public, unauthenticated page 1:1, including static content currently hardcoded in the reference's `src/json/*` files.

## Pages to port (from `cats-frontend/src/pages`)
- `Home.tsx` — hero, services teaser, FSCD partnership blurb, about teaser, service-locations section, footer contact form. Supports `?scroll=`/`#hash` section jumps.
- `About.tsx` — mission/philosophy/newcomer-support/team/approach sections (source: `json/about.tsx` → `Approach`, `Approach2`, `Philosophy`).
- `Team.tsx` — grid of team members (source: `json/temp.tsx` → `TeamsData`), mailto links.
- `Founder.tsx` — static founder bio + `json/temp.tsx` → `Values`.
- `Services.tsx` — fetches public `services` (from `service_offerings`/`services` table per Phase 2), Journey/Regulated/Funding/Why sections from `json/services.tsx`, accordion cards.
- `ServiceDetail.tsx` (`/servicesDetails/:id`) — single service, falls back to static `tempServices` if fetch fails (port the same fallback behavior).
- `Fscd.tsx` — content from `json/fscd.tsx` (`Process`, `WhyFscd`).
- `Careers.tsx` — **decision made**: unlike the reference (which uses static `json/careers.tsx` data), this rebuild wires the public Careers list to the `careers` DB table (`CareerController::index`, `is_active = true`, ordered by `due_date`/`created_at` matching reference's implicit ordering) so admin-managed postings are the single source of truth. Search/filter by position/location/experience stays client-side over the fetched set, same UX as reference.
- `CareerDetail.tsx` (`/careers/:id`) — fetch one `careers` row by id instead of a static record; same content sections (responsibilities/qualifications/skills/benefits/required_documents) + apply CTA.
- `Faq.tsx` — accordion FAQ from `json/faq.tsx`, category filter + search, custom markdown-lite parser for `**bold**`/URLs.
- `Terms.tsx`, `Privacy.tsx`, `Cookie.tsx`, `Accessibility.tsx` — static legal text.
- `NotFound.tsx` — 404.
- Footer `ContactForm.tsx` → `contacts` table via `POST /contacts`.

> Note: `json/careers.tsx` also exports non-listing constants (`ApplicationProcess`, `Benefits`, `RESIDENT_STATUS`, `WEEK_DAYS`, `EDUCATION_OPTIONS`, `LEAD_SOURCE_OPTIONS`) used by TeamMemberForm (Phase 11) and application forms (Phase 5/10) — those stay static config constants; only the job-posting list itself (`Careers` array) moves to the `careers` table.

## Components/data to port
- `json/intake.tsx` — `Provinces`, `ProvinceCities`, `days`, `times`, `leadSource`, `medicalConditionsOptions`, `servicesMap`, `allServices` (shared taxonomy used later by intake form, Phase 5, and team member specializations, Phase 11 — port once here as shared constants).
- Cookie consent banner (`CookieConsent` component rendered globally in reference's `AuthContext`/App root).

## Explicitly not building
- `pages/Index.tsx` — dead/empty file in reference, skip.
- Admin `CalendarPage.tsx` dummy calendar — not public, and dead/unwired in reference sidebar; covered (or skipped) in Phase 8 decision.

## Acceptance criteria
- All public routes render with content matching the reference visually and textually.
- Contact form submits to `contacts` table and (per Phase 15) triggers the same notification the reference sends.
- No auth required for any route in this phase.
