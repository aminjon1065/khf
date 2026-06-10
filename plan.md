# KCHS Portal Implementation Plan

> **Single source of truth** for the development of the official multilingual portal of the
> Committee for Emergency Situations and Civil Defense under the Government of the Republic of
> Tajikistan (КЧС / КҲФ). Derived from `ТЗ_Веб-сайт_КЧС.docx` (Редакция 1.0, июнь 2026).
>
> **Working rule:** Implement one unchecked task at a time. After each task: update the relevant
> checkbox, the Progress Summary, the Change Log (and Decision Log when an architectural choice was
> made), then run types/lint/tests before moving on. Never run multiple large phases in parallel.

---

## Project Overview

A single modular Laravel + Inertia (React/TypeScript) web application that fully replaces the two
legacy КЧС sites (kchs.tj, khf.tj). It serves two audiences from one codebase:

- **Public portal** — emergency awareness, interactive GIS incident map, alerts/subscriptions
  (web banner, browser push, email), news & operational summaries, safety guides, documents
  registry, citizen appeals, tourist-group registration, full-text search, accessibility mode.
- **Custom CMS** — content, media, incidents, alerts, moderation, navigation, multilingual
  versions, users/roles, settings, audit log; built on the same stack (no third-party CMS).

Mandatory languages at launch: **Tajik (tg)** and **Russian (ru)**; **English (en)** for partners.
Multilingual content uses **separate `*_translations` tables** (per ТЗ §9, Приложение Б).

**Mandated stack (ТЗ §10):** PHP 8.3+, Laravel 13, MySQL 8, Inertia.js, React 19 + TS,
Tailwind v4 + shadcn/ui (Radix) + lucide-react, Vite, MapLibre GL JS (OSM tiles), Laravel Mail via
corporate SMTP, MySQL full-text search (Scout optional), self-hosted Matomo.

**⚠️ Deployment constraint (owner decision):** Production runs on **shared hosting** (no Redis,
no long-lived daemons, no Docker/Supervisor, no Node SSR process). This deviates from several ТЗ
§10/§16 mandates; substitutions are locked in D-10…D-14 and the conflicts are tracked in Risks
R-8/R-9. Net effect: cache/session/queue use the **database** driver; realtime alerting uses
**polling** (ТЗ §10.5 fallback) instead of Reverb; **SSR is off** (SEO via server meta + sitemap +
schema.org); background work runs via a single **cron `schedule:run`**.

---

## Progress Summary

- **Total Tasks:** 196
- **Completed:** 101
- **In Progress:** 0
- **Blocked:** 0 (Redis blocker removed — D-10: no Redis on shared hosting, DB drivers in use)
- **Remaining:** 95
- **Completion:** ~52%

> Phases 0–12 substantially done; public portal (home/news/incidents/map/documents/appeals/tourism/
> subscribe + alert banner) live; alert→subscriber email dispatch closed (queued job, per-locale
> template, idempotent via `notified_at`, delivery logging); i18n foundation in place (server lang
> files + client `useTranslations`, chrome converted); 182 tests. Remaining: web push (12 tail),
> per-page string extraction + hreflang/canonical/CMS translation-status (13), search (14),
> SEO/analytics (15), security hardening + audit log (16), perf (17), testing/a11y/API (18),
> deploy (19).

> Completed = starter-kit functionality already satisfying ТЗ (auth, 2FA, passkeys, settings, SSR,
> shadcn base) + Phase 0 (audit, decisions D-1…D-9) + Phase 1 design tokens (Приложение В/Г).

---

## Current Sprint

**Phase 3 — CMS Foundation.** Phases 0–2 done. The CMS shell is up: guarded `/admin`, `AdminLayout`
+ `AdminSidebar`, dashboard, and permission-aware shared props. Next: the reusable building blocks
(server-side DataTable, form patterns, confirm-dialog) proven on a first real CRUD, then user/roles
management — before content modules (Phase 4) build on them.

---

## Phase 0 — Architecture Review

- [x] Audit existing codebase vs ТЗ (Already / Partial / Missing / Risks / Tech Debt) — see audit section below
- [x] Extract and catalogue all ТЗ functional & non-functional requirements
- [x] **D-1** RBAC → `spatie/laravel-permission` (confirmed)
- [x] **D-2** Translations → custom `*_translations` tables (confirmed, per ТЗ §9)
- [x] **D-3** Media library → `spatie/laravel-medialibrary` (confirmed)
- [x] **D-4** Audit log → `owen-it/laravel-auditing` (confirmed)
- [x] **D-5** Map → MapLibre GL JS (confirmed)
- [x] **D-6** Dependency additions required by ТЗ (Reverb, Echo, push, Redis activation) approved
- [x] Define module/domain folder structure + service/action layering — see D-8
- [x] Define routing strategy: locale URL prefix, public vs `/admin` split, `/api/v1` — see D-9
- [x] Document target architecture & data model in Decision Log (D-7…D-9)

## Phase 1 — Core Infrastructure

- [x] Configure shared-hosting drivers (no Redis): cache/session/queue on `database`; `khf` DB
  created as utf8mb4/utf8mb4_unicode_ci; baseline migrations run (D-10)
- [x] Fix `.env`/`.env.example` DB block formatting + align `.env.example` to project/MySQL (T-1)
- [x] Disable Inertia SSR (`config/inertia.php`) for shared hosting (D-12)
- [x] Apply КЧС design tokens to `resources/css/app.css` (Приложение В: primary #1F4E8C, signal #EA6A1E, destructive #DC2626, radius 0.5rem) + dark theme derivation
- [x] Add hazard-level color scale tokens (Приложение Г: норма #16A34A, готовность #EAB308, опасно #EA580C, ЧС #DC2626) as semantic tokens (`hazard-*`)
- [x] Cyrillic/Tajik-capable sans font: switched to **Inter** (Bunny self-hosted, weights 400/500/600/700) in `vite.config.ts` + `--font-sans`; build bundles Latin+Cyrillic subsets
- [→] Realtime polling: active-alerts JSON endpoint + client poll abstraction (D-11) — DEFERRED to Phase 6 (needs the `alerts` model)
- [→] MapLibre GL JS base map component + OSM tiles + offline fallback (D-5) — DEFERRED to Phase 7 (needs the map page/data)
- [ ] Configure Laravel scheduler entries (queue drain, cleanup, digests, maintenance) + document the single shared-hosting cron `schedule:run` (D-10/D-13)
- [x] Establish `languages` table + Language model (cached `active()`/`codes()`/`default()`) + seeder (tj, ru, en) + `config('app.locales')` — tested
- [x] Locale routing + `SetLocale` middleware: `/{locale}` prefix (tj|ru|en) for public content, `/` → resolved localized home, CMS/auth unprefixed; resolves URL → session → browser (tg→tj) → default — tested
- [x] Inertia shared props: current `locale`, active `locales` (code/native_name/hreflang/is_default), `localeSwitch` URL map + TS types + `LanguageSwitcher` component (wired into public header) — tested. (auth user already shared; permissions added in Phase 2, active alerts in Phase 6)
- [x] Security headers middleware (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, HSTS) — `SecurityHeaders` (global) + `config/security.php`, env-aware CSP (Vite-friendly in local), tested

## Phase 2 — Authentication & RBAC

- [x] Login (Fortify)
- [x] Password reset / forgot password (Fortify)
- [x] Email verification (Fortify)
- [x] Two-factor authentication — TOTP + recovery codes (Fortify)
- [x] Passkeys / WebAuthn (Laravel Passkeys)
- [x] Password confirmation + secure session settings
- [x] Profile & password settings pages
- [x] Roles & permissions schema via spatie/laravel-permission (published + migrated) per D-1
- [x] Seed roles: **Суперадминистратор + Модератор** (reduced from §8's six per owner; permissions stay granular so more roles are easy to add later) — `App\Enums\Role` + `RolePermissionSeeder`
- [x] Seed granular permissions (30) + map to roles (least privilege) — `App\Enums\Permission`; super-admin via `Gate::before`; moderator = content+ops, no users/roles/settings (§8)
- [~] Policies / Gates + `can:`/`role:`/`permission:` route middleware — spatie middleware aliases registered + `role:` applied to `/admin`; per-module policies as modules land
- [x] Enforce mandatory 2FA (`EnsureTwoFactorEnabled`, alias `twofactor.enforce`) for both roles — redirects to security settings; tested. Applied to `/admin` in Phase 3 (§12.3)
- [~] Login throttling (Fortify `login` limiter, 5/min — tested) + lockout; failed-attempt audit logging deferred to Phase 16 (needs audit infra) (§12.3)
- [x] Public registration disabled; admin-driven user creation flow built (`/admin/users`)
- [x] User management UI in CMS (`/admin/users`): create, edit, **block/unblock** (blocked users rejected at login via Fortify), reset password, assign role; self-target guards
- [~] Roles & permissions management UI — deferred: only 2 code-defined roles for now (D-16); a read-only roles view can follow when custom roles are needed (§8)
- [x] Tests: RBAC authorization matrix + 2FA enforcement + login throttling + blocked-login all covered

## Phase 3 — CMS Foundation

- [x] `/admin` route group guarded by `auth + verified + twofactor.enforce + role:super-admin|moderator` (`routes/admin.php`); guest→login, role-less→403, no-2FA→security — tested
- [x] CMS app shell layout (`AdminLayout` + `AdminSidebar` reusing shadcn Sidebar/NavUser, permission-aware nav, breadcrumbs header); `app.tsx` routes `admin/*` pages to it
- [x] Permission-aware UI: `auth.roles`/`auth.permissions` shared props + `usePermissions()` hook (`can`/`hasRole`)
- [x] CMS dashboard (real counts: users/languages/roles) — recent activity + active incidents/alerts to add as those modules land
- [x] Reusable CMS DataTable (`components/admin/data-table.tsx` + shadcn `ui/table`): server-side debounced search, sortable columns, pagination — proven on Languages
- [x] Reusable CMS form patterns (Form Request validation + Inertia `useForm` dialog + flash toast) — proven on Languages create/edit
- [x] Confirm-dialog pattern for dangerous ops (delete) — Languages delete dialog (§7.1)
- [x] Soft-delete + restore (trash) UX — Pages trash list + restore + force-delete; reusable pattern (§7.1)
- [~] Russian CMS interface localization — CMS UI authored in Russian; lang-file dictionaries later (§7.1)
- [x] Tablet-responsive admin panel — shadcn collapsible sidebar is responsive out of the box (§7.1)
- [x] Generic multilingual editing pattern — per-language tabs + translation-status check, proven on Pages (§7.9)

## Phase 4 — Content Management

- [x] `pages` + `page_translations` (parent, slug per lang, status, SEO, soft-delete) + model/factory + reusable `HasTranslations` trait (D-2 custom translation tables, fallback chain) — tested
- [x] `posts` + `post_translations` (PostType enum: news/release/announcement/summary; category FK; author; published_at; status; soft-delete + trash) + full CMS CRUD — tested. (cover image with media library)
- [~] `categories` done (+ translations/slug + CMS CRUD); `tags` + pivots to posts/documents pending
- [~] Status workflow: `App\Enums\ContentStatus` (draft → moderation → published → archived) done; status transitions UI + scheduled publish/unpublish pending
- [x] WYSIWYG editor (TipTap) with **server-side HTML sanitization** (symfony/html-sanitizer) — bold/italic/H2-H3/lists/quote/link + undo/redo; wired into post body + page content; sanitized HTML rendered on public article; tested (D-18). Tables/images/embeds can extend later (§7.2)
- [~] Per-material SEO fields — pages have per-locale slug + seo_title + seo_description; OG image with media library (§7.2)
- [~] Media library (spatie/laravel-medialibrary installed, D-3): post **cover** upload + thumb conversion (non-queued) + remove, wired to the post form/list — tested. Full media browser (search/reuse/galleries/alt) + page covers pending (§7.7)
- [ ] Block-based page builder (text, image/gallery, news list, map widget, CTA, accordion, table, contacts) (§7.3)
- [ ] Homepage block management (configurable composition/order without code) (§6.1, §7.3)
- [ ] Menu & navigation editor (top, footer; nesting, order, visibility per language) (§7.8)
- [ ] Version history + rollback for key materials (§7.10)
- [ ] Tests: content CRUD, status workflow, scheduling, translations, media

## Phase 5 — Emergency Incidents

- [x] Incident types as `IncidentType` enum (7 types: code/label/color/icon) + `HazardLevel` enum (Приложение Г) — instead of a table; tested
- [x] `regions` + `region_translations` (oblast hierarchy via parent_id, geocode lat/lng) + `RegionSeeder` (Душанбе/Согд/Хатлон/ГБАО, tj/ru/en) — tested. Districts can be added under parents
- [x] `incidents` + `incident_translations` (type[enum], hazard_level[enum], status[enum], region FK, occurred_at, lat/lng geometry, soft-delete) — tested
- [~] Incident CRUD in CMS (DataTable + trash + per-language tabs + type/level/status/region + manual lat/lng) — done; map-based location picker in Phase 7 (§7.4)
- [x] Status lifecycle: `IncidentStatus` (активно/под контролем/завершено) — set in CMS, reflected on public archive (§7.4)
- [~] Link incidents ↔ regions (FK) done; incident ↔ posts linking later (§6.2)
- [~] Public incidents archive (active-first ordering) — done; type/level/region/period filters later
- [x] Tests: incident CRUD, geometry, status, translations, public archive

## Phase 6 — Emergency Alerts

- [x] `alerts` + `alert_translations` (hazard_level, region, active period start/end, status, is_dismissible, soft-delete) — channels deferred to Phase 12 (push/email)
- [x] Alert lifecycle: `AlertStatus` (draft → published → cancelled); `scopeActive` honours time window (§6.4.4)
- [x] Alert CRUD in CMS (`can:alerts.manage`): multi-language, region, period, dismissible flag + trash (§6.4.4)
- [x] Site banner (`AlertBanner` in `PublicLayout`): active alerts on every public page, ordered by hazard level (server-sorted); critical pinned (non-dismissible), others dismissible (localStorage) (§6.4.1)
- [x] National vs region targeting (nullable `region_id`) (§6.4.1)
- [x] Banner refresh via **Inertia polling** (`usePoll` 60s, partial reload of `activeAlerts` shared prop) — D-11 (§10.5)
- [~] "Emergency mode": publishing a critical alert surfaces the pinned banner everywhere without a developer (§4.2) — covered by publish flow; a dedicated one-click mode toggle can follow
- [x] Tests: alert lifecycle, active scope, targeting, banner ordering (severity), CRUD, soft-delete

## Phase 7 — Interactive GIS Map

- [x] Public map page (`/{locale}/map`, MapLibre + OSM raster) + reusable `MapView`; homepage map widget pending (§6.1, §6.3)
- [~] Incident markers coloured by hazard level + popups; clustering at large counts pending (§6.3)
- [x] Click popup card: title, type, hazard level, status, region, datetime (XSS-safe DOM) (§6.3)
- [ ] Toggleable layers: incident types, risk zones, КЧС units/points (§6.3)
- [ ] Filters: type, hazard level, region/district, time period; "active only" mode (§6.3)
- [~] Admin-territorial binding (region FK) present; map filtering by region pending (§6.3)
- [~] `MapView` supports point-pick (`onPick`) for the incident form; geolocation + fullscreen pending (§6.3)
- [~] Tile source is configurable (own tile server per §10.8); graceful WebGL/offline fallback message pending (§6.3)
- [x] Tests: map data (active + with-coords only) — clustering/fallback tests later

## Phase 8 — Public Portal

- [~] Public layout: `PublicLayout` with header (logo, nav, lang switcher) + footer (trust line, committee). Search, a11y button, alert area pending (§5)
- [~] Homepage: `public/home` (hero + signal CTA, quick-access tiles, latest-news grid) replacing the starter welcome — done; alert area, operational-situation counters, map widget, subscription form pending (§6.1)
- [~] News/press-center: public listing (cards, cover thumb, pagination) + single article (recent sidebar) at `/{locale}/news[/{slug}]` — done; related-by-category, gallery, attachments, filters pending (§6.2)
- [ ] RSS feed for news (§6.2, §15.3)
- [ ] "About the Committee" section pages (leadership, structure, regional offices, history, partners, anti-corruption, vacancies) (§5)
- [ ] "Activities" section pages (§5)
- [ ] "Operational situation" section (summaries, active warnings, map, archive) (§5)
- [ ] Safety guides catalog by hazard type + guide page (illustrations, steps, downloads, print) (§6.5)
- [ ] Educational / children materials sub-section (§6.5)
- [ ] Contacts section (general, trust line/emergency numbers, regional offices, directions map, feedback) (§6.9)
- [ ] Open Graph / social preview meta + print stylesheets for guides/documents (§6.12)
- [ ] Branded error pages (404, 5xx) with nav + emergency phones (§6.12)
- [ ] Tests: public pages render, locale switching, RSS, error pages

## Phase 9 — Documents Registry

- [x] `documents` + `document_translations` (type, date, source, name/description per locale, soft-delete) + medialibrary `files` collection on the private `local` disk (§6.8)
- [x] Document categories via `DocumentType` enum (laws, regulations, departmental, plans, reports, forms) (§6.8)
- [x] Document CMS CRUD + trash with multi-file upload, mime/size validation (no executables), private storage (§6.8, §12.4)
- [~] Public registry: search + type filter + file size/name — done; date-range filter pending (§6.8)
- [x] Controlled file download route (files on private disk, streamed via route, 404 for draft) (§12.4)
- [x] Tests: document CRUD, upload, executable rejection, controlled download + draft 404, soft-delete

## Phase 10 — Appeals

- [x] `appeals` table (reference, category, contacts, subject, message, status, assignee, internal_note, soft-delete) (§6.7)
- [~] Public appeal form: category classifier, **honeypot anti-spam + `throttle:6,1`** rate limiting — done; file attachments pending (§6.7, §12.4)
- [x] Confirmation + registration number (`OBR-YYYY-XXXXXX`) shown on submit (§6.7)
- [~] CMS moderation queue: assignee, statuses, internal comments — done; deadline tracking later (§6.7, §7.6)
- [x] Public status-tracking lookup by reference number (§6.7)
- [x] Personal-data access restricted to `appeals.manage` role (§12.5)
- [ ] Register export for a period (§7.6)
- [x] Tests: submission, honeypot, validation, tracking, moderation update, authorization

## Phase 11 — Tourist Registration

- [x] `tourist_groups` table (leader/contacts, participants, route, equipment, dates, region, start coords, status, assignee, internal_note, soft-delete) (§6.6)
- [x] Public application form: leader/contacts, route, region, dates, participant count + honeypot + `throttle:6,1` (§6.6)
- [~] Region binding for risk assessment done; map route/track picker pending (§6.6)
- [x] Applicant acknowledgement: reference (`TUR-YYYY-XXXXXX`) + tracking by reference (§6.6)
- [x] CMS processing queue with statuses/assignee/note (reuses `AppealStatus`, shared UX with appeals) (§6.6, §7.6)
- [x] Personal-data protection; `tourist-groups.manage`-only handling (§6.6, §12.5)
- [x] Tests: submission, honeypot, date/required validation, tracking, moderation update, authorization

## Phase 12 — Notifications

- [x] `subscribers` (email/token, locale, status, topics[json], region, confirmation + consent dates) (§6.4)
- [x] Subscription topics as `SubscriptionTopic` enum + region (topics stored as JSON on subscriber) (§6.4)
- [x] `notifications_log` (channel, status, time, error; links to alert/subscriber) — table + model + dispatch writes (§6.4)
- [x] Email subscription with **double opt-in** (queued confirmation mail) + consent date stored (§6.4.3)
- [x] One-click unsubscribe via tokenized link (no auth) (§6.4.3)
- [x] Branded, localized email templates — confirmation + per-locale alert email (`emails/alert`) (§6.4.3)
- [x] Queued bulk email (alert→subscribers) via `SendAlertNotifications` job (cron queue, D-10) with delivery logging (§6.4.3, §10.4)
- [ ] Web push: service worker, opt-in, topic/region selection, unsubscribe (§6.4.2)
- [ ] Push delivery on alert publish (queued) (§6.4.2)
- [x] CMS: subscriber registry (search/status filter) + counts/stats (`subscribers.manage`) (§6.4.4)
- [~] Send preview + confirmation before bulk send; double-send protection — `notified_at` guard prevents re-send (preview UI pending) (§6.4.4)
- [~] Channel extensibility (SMS/messenger) — `notifications_log.channel` + topic model leave room (§6.4.4, §10.8)
- [x] Tests: double opt-in, unsubscribe, consent/topic validation, honeypot, re-subscribe, CMS registry

## Phase 13 — Multilingual System

- [x] Interface translation dictionaries (tg/ru/en) — `lang/{locale}/ui.php` shared as `translations` prop + client `useTranslations` (`t()`, dot-keys, `:placeholder`, key fallback); chrome (public layout nav/footer/brand + language switcher) converted; per-page string extraction ongoing (§14)
- [x] Language switcher on all pages; persist selection (session via SetLocale); first-visit browser detection (tg→tj) (§14)
- [ ] Locale URL prefix + hreflang + canonical generation (§14, §15.1)
- [ ] Missing-translation handling: show available version w/ note or fallback by setting (§14)
- [ ] Per-material independent language publishing + translation-status indicator in CMS (§7.9)
- [ ] Locale-aware date/number formatting (§14)
- [ ] Full Tajik Cyrillic support in fonts, search, forms, URLs/slugs (§14)
- [ ] Tests: locale resolution, fallback, hreflang, slug per language

## Phase 14 — Search Engine

- [ ] Full-text search over posts, documents, guides, pages (locale-aware) (§6.10)
- [ ] MySQL full-text indexes (+ Scout abstraction if needed) (§10) for tg/ru handling
- [ ] Results page: match highlighting, content-type filters, pagination (§6.10)
- [ ] Tests: search relevance basics, filters, Tajik/Russian queries

## Phase 15 — Analytics

- [ ] Self-hosted Matomo integration (privacy-respecting tracking) (§15.2)
- [ ] Goal tracking: subscriptions, tourist registration, appeals (§15.2)
- [ ] sitemap.xml (with language versions) + robots.txt (§15.1)
- [ ] schema.org markup (Organization, NewsArticle) (§15.1)
- [ ] 301 redirect map from legacy kchs.tj / khf.tj URLs (§15.1)
- [ ] Social account links/widgets (data/perf-safe) (§6.12, §15.3)
- [ ] Tests: sitemap generation, redirects, structured data presence

## Phase 16 — Security Hardening

- [ ] HTTPS-only + HTTP→HTTPS redirect + HSTS (§12.1)
- [ ] CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy headers verified (§12.2)
- [ ] HTML sanitization for WYSIWYG output (XSS) (§12.2)
- [ ] CSRF verified on all mutating ops; SQLi covered by Eloquent (§12.2)
- [ ] File upload hardening: type/size checks, no executables, storage outside webroot, controlled routes (§12.4)
- [ ] Anti-spam + rate limiting on all public forms (§12.4)
- [ ] Password policy, secure sessions (cookie flags, idle timeout) (§12.3)
- [ ] Audit log: `audit_logs` — who/what/when for CMS actions + security events; tamper-resistant (§7.10, §12.7)
- [ ] Secrets outside repo; dependency vulnerability checks (§12.5, §12.6)
- [ ] Security review pass (OWASP Top 10) (§18.1)
- [ ] Tests: headers, sanitization, upload rejection, rate limits, audit entries

## Phase 17 — Performance Optimization

- [ ] Database/file page+data caching with correct invalidation on publish (alerts never cache-delayed) (§10.6, §13.1; no Redis per D-10)
- [ ] HTTP cache headers + versioned static assets (§13.1)
- [ ] Image optimization + responsive/adaptive delivery + lazy loading (§13.1)
- [ ] Code splitting + response compression (gzip/brotli) (§13.1)
- [ ] DB query optimization: indexes, eliminate N+1 (eager loading), paginate large lists (§13.1)
- [ ] Move heavy ops (sends, import, image processing) to queues (§10.4)
- [ ] Load testing confirming targets (TTFB ≤600ms cached, ×10 peak) (§13, §18.1)
- [ ] Graceful degradation under load (secondary features limited, alerts/ops preserved) (§13)

## Phase 18 — Testing

- [ ] Backend unit + feature test coverage of key logic (§18.1)
- [ ] Key client-side scenario tests (Pest browser / smoke) (§18.1)
- [ ] Accessibility audit WCAG 2.1 AA + low-vision mode verification (§6.11, §11.7, §18.1)
- [ ] Cross-browser + mobile responsiveness checks (§18.1)
- [ ] Internal API (token auth, versioning, rate limit) + documentation (§10.9, §18.3)
- [ ] Low-vision accessibility mode (font size, contrast, reduced graphics, keyboard nav, screen-reader) (§6.11)
- [ ] UAT support on staging (§18.1)

## Phase 19 — Deployment

- [ ] Shared-hosting deploy guide: docroot → `public/`, SSH deploy + `migrate --force`, asset build upload (D-13; §16.1)
- [ ] Single cron entry `* * * * * php artisan schedule:run`; scheduler drains DB queue via `queue:work --stop-when-empty` (D-10; replaces Supervisor)
- [ ] Optional Docker/Compose for local dev only (app/PHP-FPM, Nginx, MySQL) — not production (D-13)
- [ ] CI/CD pipeline: lint, types, tests, Vite build, deploy (extend existing GH Actions) (§16.2)
- [ ] Env separation: dev / staging / production; secrets management (§16.1)
- [ ] Backup automation (DB + files, 30-day retention) + restore verification (§4.3, §16.3)
- [ ] Monitoring/alerting (availability, errors, queues) (§4.3, §16.3)
- [ ] TLS certificate management (§16.3)
- [ ] Deploy/runbook + admin/user (RU) + architecture + API documentation (§18.3)

---

## Decision Log

> Architectural decisions. Status: **Proposed** until confirmed by the project owner.

- **D-0 (Accepted):** Project is a greenfield build on the Laravel React Starter Kit baseline.
  Reuse existing auth/settings/shadcn foundation; do not rewrite it.
- **D-1 (Accepted 2026-06-09):** RBAC via **`spatie/laravel-permission`** (configurable roles +
  granular permissions, satisfies §8).
- **D-2 (Accepted):** Multilingual content via **custom `*_translations` tables + `HasTranslations`
  trait** — explicitly required by ТЗ §9 / Приложение Б. UI strings via Laravel lang files + a
  shared Inertia dictionary.
- **D-3 (Accepted 2026-06-09):** Media library via **`spatie/laravel-medialibrary`** (conversions,
  responsive images, polymorphic associations — satisfies §7.7).
- **D-4 (Accepted 2026-06-09):** Audit log via **`owen-it/laravel-auditing`** for §7.10/§12.7
  (tamper-resistant CMS action log).
- **D-5 (Accepted 2026-06-09):** Map via **MapLibre GL JS** (vector rendering, OSM-compatible,
  custom tile-server ready per §10.8).
- **D-6 (SUPERSEDED by D-10/D-11 2026-06-09):** Originally Reverb + Echo + pusher-js + Redis.
  Dropped due to the shared-hosting target. Web-push library is still added (works via cron queue).
- **D-10 (Accepted 2026-06-09):** No Redis (shared hosting). cache = `database`, session =
  `database`, queue = `database`. Background/long tasks (bulk email, push, image processing,
  digests, cleanup) run through the queue, drained by a single cron `* * * * * php artisan
  schedule:run` that dispatches `queue:work --stop-when-empty`. No Supervisor.
- **D-11 (Accepted 2026-06-09):** Realtime via **polling**, not WebSockets/Reverb. The active-alert
  banner + operational counters are fetched periodically (Inertia poll / lightweight JSON endpoint,
  ~30–60s) and on navigation. ТЗ §10.5 explicitly allows polling as the fallback. A thin
  `Broadcast`-style abstraction keeps the door open to enable Reverb later on a VPS.
- **D-12 (Accepted 2026-06-09):** Inertia SSR **disabled** (`config/inertia.php`). SEO delivered via
  server-rendered `<title>`/meta/OG/canonical/hreflang (Inertia head + blade root), `sitemap.xml`,
  `robots.txt`, and schema.org JSON-LD. Satisfies §15.1 intent without a Node daemon.
- **D-13 (Accepted 2026-06-09):** Deployment = shared hosting with **SSH + cron**. Docroot →
  `public/`. Deploy/migrate via SSH artisan. Docker/Compose kept (optionally) for local dev only,
  not for production. Phase 19 targets this model, not containers.
- **D-14 (Revised 2026-06-09, owner preference):** Internal locale codes are **`tj` / `ru` / `en`**
  (owner chose `tj` over ISO `tg`). To keep SEO valid, the `languages` table carries a separate
  **`hreflang`** column emitting BCP-47 tags (`tj → tg`, `ru`, `en`) for hreflang/canonical output
  (§15.1). `config('app.locales')` = `['tj','ru','en']` as a static fallback; the `languages` table
  is the canonical, CMS-editable source.
- **D-15 (Accepted 2026-06-09, owner-confirmed):** Locale routing. Public content under `/{locale}`
  for **all** locales incl. default (clean hreflang); `/` (named `home`) redirects to the resolved
  localized homepage (named `welcome`). Fortify auth + `/admin` CMS + settings stay **unprefixed**
  (CMS UI Russian; avoids Fortify route conflicts) and resolve locale via session/browser.
  `SetLocale` is appended to the `web` group (before Inertia share) and handles both. Frontend
  `home()` stays no-arg (`/`), so existing auth-layout links and the type-check are unaffected.
- **D-18 (Accepted 2026-06-09, owner):** WYSIWYG via **TipTap** (frontend, `@tiptap/react` v3 +
  starter-kit + link) emitting HTML, sanitised server-side by **symfony/html-sanitizer**
  (`App\Support\HtmlSanitizer`, `allowSafeElements`) before storage (§7.2/§12.2). Chosen
  symfony/html-sanitizer over mews/purifier for clean Laravel 13 / PHP 8.4 compatibility (no version
  constraints, no config publishing). Rich-text rendered within `.rte-content` (Tailwind preflight
  resets are restored there).
- **D-16 (Revised 2026-06-09, owner):** RBAC design. Permissions are `module.action` strings in
  `App\Enums\Permission` (30); roles in `App\Enums\Role`. **Owner reduced the role set to two for
  now: Суперадминистратор + Модератор** (the full §8 six-role model is deferred; permissions stay
  granular so adding roles later is trivial). Super-admin = everything via `Gate::before` (covers
  policies/abilities without a named perm). Moderator = all content + emergency operations
  (incidents/alerts/map, `alerts.send`) + appeals/tourist/subscribers/media, but **not** users,
  roles, or settings management (super-admin only). Both roles require 2FA. Roles stay
  runtime-editable in the CMS.
- **D-7 (Accepted 2026-06-09):** Signal-orange calibration. ТЗ Приложение В maps the orange to
  shadcn's `--accent`, but `--accent` drives every hover/active surface — making all hovers orange
  contradicts §11.2 ("дозированно") and harms a11y. Therefore `--accent` stays a calm blue-tinted
  surface and the signal-orange is exposed as a dedicated `--signal` / `bg-signal` token used only on
  deliberate CTAs. Hazard scale exposed as `hazard-{normal,elevated,danger,critical}` tokens.
- **D-8 (Accepted 2026-06-09):** Code organisation. Thin controllers in
  `app/Http/Controllers/{Public,Admin}`; Form Requests in `app/Http/Requests/{Domain}`; business
  logic in `app/Actions/{Domain}` and `app/Services/{Domain}`; `app/Policies`, `app/Enums`,
  `app/Events`, `app/Jobs`, `app/Notifications`, `app/Support`. Models stay in `app/Models`.
  Repositories only where a query surface genuinely warrants one.
- **D-9 (Accepted 2026-06-09):** Routing. Public routes under locale prefix `/{locale}` (tg|ru|en),
  `/` redirects to default locale; `SetLocale` middleware resolves URL → session → browser →
  fallback. CMS under `/admin` (Russian UI, no locale prefix) guarded by auth+verified+role(+2FA).
  Internal API under `/api/v1` with token auth (Sanctum to be added in the API task, §10.9).

## Change Log

- **2026-06-10** — Phase 13 i18n foundation: `lang/{tj,ru,en}/ui.php` interface dictionaries
  (site/nav/footer/lang keys, identical across locales); `HandleInertiaRequests` shares the active
  locale's `ui` array as the `translations` prop; client `useTranslations` hook (`t()` with
  dot-notation lookup, `:placeholder` interpolation, key fallback) + `Translations` type. Public
  layout (brand/nav/footer) + language switcher aria converted off hard-coded Russian. 3 feature
  tests (per-locale dictionary + key-parity guard); 182 total. types/build/lint/Pint clean.
  Remaining: per-page string extraction, hreflang/canonical, CMS translation-status indicator.
- **2026-06-10** — Phase 12 alert→subscriber dispatch: `add_notified_at_to_alerts_table` migration +
  `Alert.notified_at` cast; queued `AlertNotification` mailable + per-locale `emails/alert` markdown
  template (title/level/body + tokenized unsubscribe subcopy); `SendAlertNotifications` job —
  `AlertController` store/update dispatch it only when `status===Published && notified_at===null`
  (double-send guard). Job emails confirmed subscribers with the `alerts` topic, region-targeted
  (alert region OR all-region subscribers), `chunkById(200)`, writes `notifications_log`, stamps
  `notified_at`. 5 feature tests (179 total). Pint clean. Web push + send-preview UI still deferred.
- **2026-06-10** — Phase 12 Notifications (email subscriptions): `SubscriptionStatus`/
  `SubscriptionTopic` enums; `subscribers` (email/token, locale, status, topics json, region,
  confirm + consent dates) + `notifications_log` table/model. Public subscribe form (consent +
  honeypot + `throttle:6,1`) → **double opt-in** via queued `SubscriptionConfirmation` mail →
  tokenized confirm + one-click unsubscribe. CMS subscriber registry (search/status filter + stats,
  `subscribers.manage`). Public «Подписка» + CMS «Подписчики» nav. 8 feature tests (174 total).
  types/build/lint/Pint clean. Mass alert→subscriber dispatch + web push deferred.
- **2026-06-10** — Phase 11 Tourist-group registration: `tourist_groups` (leader/contacts, route,
  equipment, dates, region, status [reuses `AppealStatus`], assignee, note, soft-delete) + `TUR-…`
  reference. Public form (`throttle:6,1` + honeypot) → confirmation + reference tracking; CMS queue
  (search/status filter) + detail/assign/status/note (`tourist-groups.manage`). Public «Туризм» +
  CMS «Тургруппы» nav. 7 feature tests (166 total). types/build/lint/Pint clean.
- **2026-06-09** — Phase 10 Appeals (electronic reception): `AppealCategory` + `AppealStatus` enums;
  `appeals` table (reference, contacts, subject/message, status, assignee, internal_note,
  soft-delete). Public form (`Public\AppealController`) with **honeypot + `throttle:6,1`**, a
  unique `OBR-YYYY-XXXXXX` reference + confirmation screen, and reference-based status tracking. CMS
  moderation queue (`Admin\AppealController`, `can:appeals.manage`): list+filter, detail with full
  personal data, assign/status/internal-note update. Nav: «Обращения» (CMS) + «Приёмная» (public).
  8 feature tests (159 total). types/build/lint/Pint clean. (Attachments + register export deferred.)
- **2026-06-09** — Phase 9 Documents registry: `DocumentType` enum; `documents` +
  `document_translations` (type/source/date/status, name+description per locale, soft-delete) +
  medialibrary `files` collection on the **private `local` disk** (§12.4). CMS CRUD + trash
  (`Admin\DocumentController`, `can:documents.manage`) with multi-file upload (mime allowlist, ≤20 MB,
  no executables) + per-file removal. Public registry (`Public\DocumentController`) with search +
  type filter, and a **controlled download route** (streams from private disk, 404 for drafts).
  CMS «Документы» nav + public «Документы» link. 7 feature tests (152 total). types/build/lint/Pint
  clean.
- **2026-06-09** — Phase 7 GIS map (D-5): installed `maplibre-gl` v5. Reusable `MapView` (OSM raster
  tiles, navigation control, colour-coded markers with XSS-safe DOM popups, optional `onPick` point
  picker). `Public\MapController` → `public/map` at `/{locale}/map` plotting active incidents with
  coordinates; «Карта» nav link. Map bundle is a lazy 1 MB chunk (map page only). 1 feature test
  (145 total). types/build/lint/Pint clean; live `/tj/map` & `/ru/map` → 200.
- **2026-06-09** — Phase 6 Alerts + site banner: `AlertStatus` enum; `alerts` + `alert_translations`
  (hazard_level, region FK [null=national], status, is_dismissible, start/end window, soft-delete).
  CMS CRUD + trash (`Admin\AlertController`, `can:alerts.manage`). `Alert::scopeActive` (published +
  in-window). Shared `activeAlerts` (severity-ordered, localized) in `HandleInertiaRequests`;
  `AlertBanner` in `PublicLayout` renders them on every public page — critical pinned, others
  dismissible (localStorage) — refreshed via Inertia `usePoll` (60s partial reload, D-11). Nav:
  «Оповещения». 7 feature tests (144 total). types/build/lint/Pint clean.
- **2026-06-09** — Phase 5 Incidents module: `IncidentStatus` enum; `incidents` +
  `incident_translations` (type/hazard_level/status enums, region FK, occurred_at, lat/lng,
  soft-delete). CMS CRUD + trash (`Admin\IncidentController`, `can:incidents.manage`) with form
  (type/level/status/region/coords/date + per-language tabs, hazard colour badges) + public archive
  (`Public\IncidentController` at `/{locale}/incidents`, active events first). Nav: «События ЧС»
  (CMS) + «Обстановка» (public). 7 feature tests (137 total). Fixed same-timestamp migration order.
  types/build/lint/Pint clean.
- **2026-06-09** — Phase 4 WYSIWYG (D-18): installed TipTap (`@tiptap/react` v3, starter-kit, link)
  + `symfony/html-sanitizer`. `RichTextEditor` component (toolbar: bold/italic/H2-H3/lists/quote/
  link/undo/redo) replaces the body/content textareas in the post & page forms (keyed per locale).
  `App\Support\HtmlSanitizer` sanitises body/content server-side before storage (XSS, §12.2); public
  article renders sanitised HTML in `.rte-content` (app.css restores heading/list styling under
  Tailwind preflight). 3 tests (130 total). types/build/lint/Pint clean. Note: pre-existing
  `shell-quote`/`concurrently` dev-only npm advisory unrelated to TipTap.
- **2026-06-09** — Phase 5 emergency reference data: `IncidentType` enum (7 hazard types with
  label/color/icon + `options()`), `HazardLevel` enum (норма/повышенная/опасно/ЧС mapped to
  Приложение Г colours + `hazard-*` tokens), and `regions` + `region_translations` taxonomy
  (parent hierarchy, geocode) with `RegionSeeder` (Dushanbe + 3 provinces, tj/ru/en). 5 feature
  tests (127 total). Fixed another same-timestamp migration order (region_translations after
  regions). Pint clean.
- **2026-06-09** — Phase 8 public homepage: `Public\HomeController` renders `public/home` (hero +
  orange signal CTA, quick-access tiles, latest-news grid) at `/{locale}` (route name kept
  `welcome`). Replaced the starter `welcome.tsx` (removed) — `app.tsx` welcome case dropped, public
  pages use `PublicLayout`. Added a `signal` Button variant (КЧС orange CTA, D-7). 1 homepage test
  (122 total); updated SharedLocaleTest to assert `public/home`. types/build/lint/Pint clean; live
  `/tj` → 200, `/` → 302.
- **2026-06-09** — Phase 8 public portal (start): `Public\PostController` (index + show) serving
  published, locale-translated posts at `/{locale}/news` and `/{locale}/news/{slug}` (404 for
  draft/unknown). New `PublicLayout` (brand-blue header: logo, Главная/Новости nav, language
  switcher, login; footer with trust line) mapped via `app.tsx` for `public/*` pages. News listing
  (cover cards + pagination) + single article (recent-news sidebar). 4 feature tests (121 total).
  types/build/lint/Pint clean; live `/tj/news` & `/ru/news` → 200, unknown slug → 404.
- **2026-06-09** — Phase 4 media (D-3): installed `spatie/laravel-medialibrary` v11 (media table +
  config, `MEDIA_DISK=public`, storage:link). `Post` implements `HasMedia` with a single-file
  `cover` collection + non-queued `thumb` 480×320 conversion (no queue worker on shared hosting,
  D-10). Cover upload/replace/remove wired through `PostController::syncCover` (multipart Inertia
  form) + validation (image, ≤5 MB); thumbnail shown in the post list and form. 1 feature test
  (Storage::fake) — 117 total. types/build/lint/Pint clean.
- **2026-06-09** — Phase 4 Posts module (`/admin/posts`, `can:posts.manage`): full CRUD + trash on
  the established patterns. `PostType` enum (news/press_release/announcement/summary), `posts`
  (type/category FK/author/published_at/status/soft-delete) + `post_translations`
  (title/slug/excerpt/body/SEO per locale). `PostController` (search/sort/paginate, author
  auto-set, scheduled `published_at`, single-locale slug uniqueness) + Store/Update Form Requests.
  Frontend: index (DataTable w/ type+category+status), create/edit form (type/category/status/
  publish-date + per-language tabs incl. excerpt/body), trash; «Новости» nav. Fixed a same-timestamp
  migration ordering bug (post_translations renamed to run after posts). 7 feature tests (116 total).
  types/build/lint/Pint clean.
- **2026-06-09** — Phase 4 Categories module (`/admin/categories`, `can:categories.manage`): full
  CRUD reusing `HasTranslations` + DataTable + per-language form patterns. `Category` +
  `category_translations` (name/slug per locale, unique (category_id,locale)+(locale,slug)),
  controller + Store/Update Form Requests (default-locale name required, Cyrillic-safe slug),
  index + form (per-language tabs) frontend, «Рубрики» nav. 7 feature tests (109 total).
  types/build/lint/Pint clean.
- **2026-06-09** — Phase 4 Pages CMS module (`/admin/pages`, `can:pages.manage`): full CRUD +
  soft-delete trash (list/restore/force-delete). `PageController` (search/sort/paginate, per-locale
  slug uniqueness, single default, empty-locale skip) + Store/Update Form Requests (dynamic
  per-locale rules, Cyrillic-safe slug regex, default-locale required). Frontend: index (DataTable),
  create/edit form with **per-language tabs + translation-status check** + status/parent/SEO,
  trash screen; `ui/textarea`; Pages nav (Контент group). 9 feature tests (102 total).
  types/build/lint/Pint clean. Establishes the multilingual-editing + trash UI patterns (closes the
  remaining Phase 3 patterns).
- **2026-06-09** — Phase 4 content foundation: reusable `HasTranslations` trait (custom
  `*_translations` tables per D-2: convention `<Model>Translation` / `<model>_id`, locale fallback
  chain, `upsertTranslations`), `App\Enums\ContentStatus` (draft/moderation/published/archived), and
  the `pages` + `page_translations` schema (parent hierarchy, status, sort, soft-delete; per-locale
  title/slug/content/SEO, unique (page_id,locale) + (locale,slug)) with models + factories.
  6 feature tests (93 total). Pint clean. Establishes the multilingual + soft-delete backbone for all
  content modules.
- **2026-06-09** — Phase 2/3 User management (`/admin/users`, `can:users.manage` → super-admin):
  `UserController` (search/sort/paginate + create/update/destroy + block/unblock toggle, self-target
  guards) + Store/Update Form Requests. Added `blocked_at` column + `User::isBlocked()`; Fortify
  `authenticateUsing` now rejects blocked accounts at login (covers password + 2FA pre-check).
  Frontend users page reuses DataTable + form/confirm dialogs (role select, password reset);
  permission-aware Users nav. 10 feature tests (87 total). types/build/lint/Pint clean.
- **2026-06-09** — Phase 3 reusable CRUD patterns, proven on Languages management
  (`/admin/languages`, gated `can:settings.manage` → super-admin only): `LanguageController`
  (index search/sort/paginate + store/update/destroy, single-default enforcement, default-language
  delete guard) + Store/Update Form Requests. Frontend: reusable `DataTable` + shadcn `ui/table`,
  Inertia `useForm` create/edit dialog, delete confirm-dialog; added Languages nav (permission-aware).
  9 feature tests (77 total). types/build/lint/Pint clean.
- **2026-06-09** — Phase 3 CMS shell: `/admin` route group (`routes/admin.php`) guarded by
  `auth + verified + twofactor.enforce + role:super-admin|moderator`; `Admin\DashboardController` +
  `admin/dashboard` Inertia page with real counts. New `AdminLayout` + `AdminSidebar` (shadcn
  Sidebar/NavUser, permission-aware nav, Russian); `app.tsx` maps `admin/*` → AdminLayout. Added
  `auth.roles`/`auth.permissions` shared props + `usePermissions()` hook (completes the deferred
  Phase 2 shared-permissions item). Registered spatie `role`/`permission`/`role_or_permission`
  middleware aliases. 4 access-control tests (68 total). types/build/lint/Pint clean; live guard
  verified (guest /admin → 302 /login).
- **2026-06-09** — Phase 2 RBAC foundation: installed `spatie/laravel-permission` v8 (published +
  migrated). Added `App\Enums\Permission` (30 granular perms), `RolePermissionSeeder` (idempotent),
  `HasRoles` on `User`, and a `Gate::before` granting super-admin everything. Dev test user seeded
  as super-admin. Pint clean.
- **2026-06-09** — Phase 2 role-set reduced (owner): `App\Enums\Role` now **Суперадминистратор +
  Модератор** (was six). super-admin = 30 perms, moderator = 26 (content + ops, no
  users/roles/settings). Removed the 5 stale dev-DB roles. Plus mandatory-2FA enforcement:
  `EnsureTwoFactorEnabled` middleware (alias `twofactor.enforce`) redirects privileged users
  without confirmed 2FA to security settings. Rewrote the RBAC test matrix + added 3 enforcement
  tests. Suite 64 passed; Pint clean.
- **2026-06-09** — Completed Phase 0 audit; extracted full ТЗ requirement set; authored `plan.md`
  as the master plan. No application code changed.
- **2026-06-09** — Phase 0 closed: locked D-1…D-9 (owner-approved package strategy = spatie
  permission/medialibrary/auditing, MapLibre GL JS, ТЗ-mandated deps, code organisation & routing).
- **2026-06-09** — Phase 1: applied КЧС design tokens to `resources/css/app.css` (Приложение В
  light + derived dark theme, radius 0.5rem), added dedicated `signal` token and
  `hazard-{normal,elevated,danger,critical}` scale (Приложение Г), recoloured chart tokens to the
  brand palette. Verified with `npm run build` (passes).
- **2026-06-09** — Phase 1 infra: created MySQL `khf` database as `utf8mb4 / utf8mb4_unicode_ci`
  (mitigates R-3 at the DB layer), ran baseline migrations.
- **2026-06-09** — Phase 1 security headers: `SecurityHeaders` middleware (global) + tunable
  `config/security.php` — sets X-Content-Type-Options, X-Frame-Options, Referrer-Policy,
  Permissions-Policy (geolocation=self for the map), X-Permitted-Cross-Domain-Policies, an
  env-aware CSP (Vite/HMR-friendly in local), and HSTS over HTTPS outside local. 4 Pest tests
  (56 total). Verified live: `GET https://khf.test/tj` → 200 with all headers. CSP nonce hardening
  deferred to Phase 16. Phase 1 closed (MapLibre→Phase 7, polling→Phase 6 with their modules).
- **2026-06-09** — Phase 1 typography: replaced `Instrument Sans` with **Inter** (full Cyrillic
  incl. Tajik glyphs ҷ/ҳ/қ/ғ/ӯ/ӣ) in `vite.config.ts` (Bunny self-hosted) and `--font-sans`. Build
  bundles Inter 400/500/600/700 across Latin+Cyrillic subsets. Suite 52 passed.
- **2026-06-09** — Phase 1 locale shared props: `HandleInertiaRequests::share()` now exposes
  `locale`, `locales` (active, ordered), and a path-preserving `localeSwitch` URL map; added
  `AppLanguage` TS type + `sharedPageProps` typing; built a reusable `LanguageSwitcher` (shadcn
  dropdown) wired into the welcome header. Added 2 Pest tests (52 total). types/build/lint/Pint clean.
- **2026-06-09** — Phase 1 locale routing: `SetLocale` middleware (URL→session→browser(tg→tj)→
  default, persisted to session) appended to the `web` group; `routes/web.php` now has `/` → resolved
  localized home (`home`) + `/{locale}` public group (`welcome`); CMS/auth stay unprefixed (D-15).
  Updated placeholder `ExampleTest`, added 6 `SetLocaleTest` cases, regenerated Wayfinder
  (`--with-form`). Full suite 50 passed; types/lint/build/Pint all clean.
- **2026-06-09** — Phase 1 multilingual foundation: `languages` table (code/native_name/hreflang/
  direction/is_active/is_default/sort_order) + `Language` model (cached `active()`, `codes()`,
  `default()`, `isSupported()`) + idempotent `LanguageSeeder` (tj default, ru, en; tj→hreflang tg)
  + `config('app.locales')`. Aligned env (`APP_FAKER_LOCALE=ru_RU`). Added 5 Pest tests. Full
  suite 42 passed, Pint clean. Verified seeded Cyrillic (`Тоҷикӣ`) end-to-end.
- **2026-06-09** — **Shared-hosting pivot** (owner: prod = shared hosting). Locked D-10…D-14:
  dropped Redis (D-6 superseded), realtime → polling, SSR → off, deploy → cron+SSH (no
  Docker/Supervisor in prod), locale codes → tg/ru/en. Applied: disabled SSR in
  `config/inertia.php`; cleaned `.env` (removed Redis block, fixed DB formatting); rewrote
  `.env.example` for MySQL + project defaults. Updated Phases 1/6/17/19 and Risks. `php artisan
  test --compact` → 37 passed.

## Risks

- **R-1** Realtime alerting now uses polling (D-11), so the banner has up to ~30–60s latency vs
  instant WebSocket push. Acceptable per §10.5, but emergency-mode UX must make this clear and keep
  the poll lightweight so it survives peak load.
- **R-2 / R-8 (HIGH, owner-accepted spec deviation)** Shared hosting conflicts with several ТЗ
  mandates: §13 (×10 peak load + horizontal scaling), §4.3 (99.5% uptime, RTO≤4h/RPO≤24h), §10
  (Redis, Reverb), §16 (Docker, Supervisor, zero-downtime, multi-env scaling). Mitigations:
  aggressive DB/file caching + HTTP caching + CDN-style static versioning; keep the alert path
  cheap and uncached-stale-free. **Recommend revisiting the hosting choice before launch if real
  ЧС-peak traffic or strict uptime/RTO are contractual.** Document the accepted deviation for UAT.
- **R-3** Full Tajik Cyrillic support across fonts, MySQL full-text search, slugs and URLs (§14)
  can surface collation/encoding issues — validate early (utf8mb4 + correct collation).
- **R-4** Web push reliability varies by browser/OS; email remains the guaranteed channel.
- **R-5** Self-hosting (Matomo, MinIO/S3, SMTP, tile server) for data sovereignty (§12.6) adds
  ops surface; must be containerized and documented.
- **R-6** Personal-data handling (appeals, tourist groups, subscribers) requires strict RBAC +
  consent tracking + deletion (§12.5) — privacy bugs are high-severity.
- **R-7** Scope is large (ТЗ estimates 7–10 months); strict per-task discipline via this plan is
  required to avoid half-finished modules.
- **R-9** Cron-driven queue (D-10) drains at most once per minute, so bulk email/push sends and
  acknowledgements have up-to-1-minute latency and run in time-boxed batches. Fine for digests and
  mass sends; ensure critical alert emails are prioritised in the queue.

## Next Action

i18n foundation in place ✅ (lang files + `useTranslations` hook, public chrome converted, 182
tests). Next options: (a) **finish Phase 13** — extract remaining hard-coded strings across public
pages/CMS into the `ui` dictionary + add `hreflang`/canonical meta and a CMS translation-status
indicator; (b) **Phase 14 search** (MySQL full-text over posts/documents/pages, locale-aware); (c)
**Phase 15 SEO** (sitemap, meta tags, Matomo). Recommend (a) to land the multilingual UI fully,
then (b) so content is findable. Web push (12 tail) stays deferred — lowest ROI on shared hosting.

---

## Audit Findings (Phase 0 reference)

### Already Implemented
- Laravel 13 + Inertia v3 (SSR enabled) + React 19 + TypeScript + Tailwind v4 + shadcn/ui base.
- Fortify auth: login, password reset, email verification, 2FA (TOTP + recovery), password
  confirmation. Passkeys/WebAuthn. Public registration intentionally disabled (fits §7.1).
- Settings: profile, password/security, appearance. Starter dashboard + welcome.
- MySQL configured (`DB_CONNECTION=mysql`, db `khf`); default locale `tg`/`tj` (Tajik-first).
- shadcn/ui component set, app/auth/settings layouts, hooks, Wayfinder typed routes.
- GitHub Actions: lint + tests workflows. Pest test suite for auth/settings.

### Partially Implemented
- **Auth/RBAC:** auth done; **roles/permissions entirely missing** (Phase 2).
- **Design system:** shadcn token plumbing present but uses default grayscale palette — **not** the
  КЧС palette (Приложение В) or hazard scale (Приложение Г).
- **Infra:** Redis configured in `.env` but cache/queue/session still on `database`; Reverb absent.
- **Inertia shared props/locale:** basic shared props exist; no locale/permissions/alerts sharing.

### Missing (everything domain-specific)
- RBAC; CMS shell; all content modules (pages/posts/categories/tags/media/menus/blocks);
  incidents + GIS map; alerts + realtime + push + email subscriptions; public portal sections;
  documents registry; appeals; tourist registration; multilingual content + `*_translations`;
  full-text search; Matomo/SEO/sitemap/redirects; security hardening (CSP/headers/audit log);
  performance/caching; Docker/Supervisor/CI-CD-deploy; API; accessibility mode.

### Risks
- See Risks section above (R-1…R-7).

### Technical Debt
- **T-1 (RESOLVED 2026-06-09)** `.env`/`.env.example` DB block formatting fixed; `.env.example`
  aligned to MySQL + project defaults.
- **T-2** `database/database.sqlite` present though MySQL is the target — remove/ignore to prevent
  accidental use.
- **T-3 (RESOLVED 2026-06-09)** Starter grayscale + `chart-*` tokens replaced with the КЧС palette.
- **T-4** Domain folder structure decided (D-8) but not yet scaffolded — create dirs as the first
  modules land so structure is established before they proliferate.
