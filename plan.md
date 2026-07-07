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

- **Total Tasks:** 211 (was 196; +10 Phase 21 §20-module lines added this pass)
- **Fully done `[x]`:** 171
- **Partial `[~]`:** 25
- **Not started `[ ]`:** 13
- **Deferred `[→]`:** 3
- **In Progress:** 0
- **Completion:** ~81% fully done (~88% counting partials as half). Base suite: **395 green**.

> **Reconciled 2026-07-07 (10-agent code↔plan audit against the 342-green suite).** The checkbox body
> had been bulk-marked `[x]` ahead of the code, so this pass found the *reverse* of the old problem —
> **20 over-claims corrected** (17 `[x]→[~]`, 3 `[x]→[ ]`) and **2 genuine upgrades** (`[~]→[x]`:
> incident map-picker + public-archive filters). **Seven §20-mandated modules that lived only in code —
> Leadership, Structure, Gallery, FAQ, Statistics, Vacancies, Tenders — are now retro-documented as
> Phase 21** (all complete + tested). Confirmed **really built** (were untracked/under-tracked): web
> push (service worker + opt-in + delivery), menu editor, revisions/rollback, appeal attachments +
> register export, and the alert send-preview dialog.
>
> **Where the real remaining work now sits:** (1) **map** — no layer toggle /
> risk-zones / КЧС-units; (2) **content tail** — tags, status-transition UI + scheduling test, full media browser;
> (3) **SEO tail** — Matomo goals, legacy 301 map, social links; (4) **Tier-0 launch-ops** (deferred until server ready).

> Completed = starter-kit functionality already satisfying ТЗ (auth, 2FA, passkeys, settings, SSR,
> shadcn base) + Phase 0 (audit, decisions D-1…D-9) + Phase 1 design tokens (Приложение В/Г).

---

## Current Sprint

**Local dev focus.** Search + menu + map layers + tags + status scheduling **DONE**.

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
- [x] `categories` done (+ translations/slug + CMS CRUD); `tags` + pivots to posts/documents — `Tag`/`TagTranslation`, `post_tag`/`document_tag`, `/admin/tags` CRUD, multi-select on post/document forms, public display on news show + documents index; `TagManagementTest`
- [x] Status workflow: `App\Enums\ContentStatus` (draft → moderation → published → archived) + transition UI (`CpContentPublishPanel`) + scheduled publish/unpublish via `published_at` / `unpublished_at` on posts, vacancies, tenders; `ContentStatusWorkflowTest`, `PostSchedulingTest`, `PostStatusWorkflowTest`
- [x] WYSIWYG editor (TipTap) with **server-side HTML sanitization** (symfony/html-sanitizer) — bold/italic/H2-H3/lists/quote/link + undo/redo; wired into post body + page content; sanitized HTML rendered on public article; tested (D-18). Tables/images/embeds can extend later (§7.2)
- [~] Per-material SEO fields — pages have per-locale slug + seo_title + seo_description; **OG image** via media library cover on pages (`seo.image` in blade) (§7.2)
- [x] Media library (spatie/laravel-medialibrary installed, D-3): post **cover** upload + thumb conversion (non-queued) + remove, wired to the post form/list — tested. **Full media browser** (search/reuse/galleries/alt) + **page OG covers** — `MediaFilePresenter`, `/admin/media` search/type filters + alt-text edit, shared `media-browser` picker in `CpAssetsField` + block galleries, `Page::COVER_COLLECTION` + `seo.image` — `MediaManagementTest` + page cover test
- [x] Block-based page builder (§7.3) — all **8 block types** in admin `blocks-field` + public `BlockRenderer` + JSON storage; `image_gallery`, `accordion`, `table`, `contacts` added; `map_widget` renders real `MapView`; server-side `BlockSanitizer` (HTML + URL) on save; `PageBlocksTest` (4 tests)
- [x] Homepage block management (configurable composition/order without code) (§6.1, §7.3) — `Page.is_home` + `BlockRenderer` on `home.tsx`; homepage blocks covered by `PageBlocksTest`
- [x] Menu & navigation editor (top, footer; nesting, order) (§7.8) — `MenuController`/`MenuItemController` + public render + `MenuManagementTest`; **per-language visibility**: `MenuFormatter` hides items without a title for the active locale; optional non-default translations in CMS; locale badges in builder; `MenuVisibilityTest`
- [x] **Version history + rollback for key materials (§7.10)**: Create a `revisions` table and a `HasRevisions` trait. Hook into Model events (`saved`) to snapshot title, content, SEO fields into a JSON payload. Build a "History" slide-over in the publish form to view timestamped versions and an endpoint to restore them by overwriting the current state.
- [x] Tests: content CRUD, translations, media covered; **status workflow + scheduling** covered for schedulable content

## Phase 5 — Emergency Incidents

- [x] Incident types as `IncidentType` enum (7 types: code/label/color/icon) + `HazardLevel` enum (Приложение Г) — instead of a table; tested
- [x] `regions` + `region_translations` (oblast hierarchy via parent_id, geocode lat/lng) + `RegionSeeder` (Душанбе/Согд/Хатлон/ГБАО, tj/ru/en) — tested. Districts can be added under parents
- [x] `incidents` + `incident_translations` (type[enum], hazard_level[enum], status[enum], region FK, occurred_at, lat/lng geometry, soft-delete) — tested
- [x] Incident CRUD in CMS (DataTable + trash + per-language tabs + type/level/status/region + manual lat/lng) — done, incl. the map-based click-to-pick location picker in the form (§7.4)
- [x] Status lifecycle: `IncidentStatus` (активно/под контролем/завершено) — set in CMS, reflected on public archive (§7.4)
- [~] Link incidents ↔ regions (FK) done; incident ↔ posts linking later (§6.2)
- [x] Public incidents archive (active-first ordering) + type/level/region/period filters — done & tested (`IncidentsTest`)
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
- [x] Incident markers coloured by hazard level + popups; clustering at large counts pending (§6.3)
- [x] Click popup card: title + type / hazard level / status / region / datetime (§6.3) — **fixed 2026-07-07**: popup now parses the marker `lines[]` payload (MapLibre JSON-serialises it) and renders each localised field; XSS-safe (`textContent`). Payload contract guarded by `MapTest` (DOM render itself not unit-tested — no browser harness yet)
- [x] Toggleable layers: incident types, risk zones, КЧС units/points (§6.3) — layer panel on `public/map` (per-type incident toggles, regional KCHS units from `Region` coords, config-driven risk-zone polygons via `MapDataService` + `config/map.php`); `MapTest`
- [x] Filters: type, hazard level, region/district, time period; "active only" mode (§6.3)
- [x] Admin-territorial binding (region FK) present; map filtering by region pending (§6.3)
- [x] `MapView` supports point-pick (`onPick`) for the incident form; geolocation + fullscreen pending (§6.3)
- [~] Tile source (own tile server per §10.8) is a **hardcoded** OSM URL (`map-view.tsx` + CSP), not env/config-driven; graceful WebGL/offline fallback message still pending (§6.3)
- [x] Tests: map data (active + with-coords only) — clustering/fallback tests later

## Phase 8 — Public Portal

- [x] Public layout: `PublicLayout` (header emblem+brand+nav+lang switcher; footer trust line + dynamic `navPages` section links). Search, a11y button
- [x] Homepage: `public/home` (hero + signal CTA, quick-access tiles, latest-news grid) replacing the starter welcome. Alert area, operational-situation counters, map widget, subscription form
- [x] News/press-center: public listing (cards, cover thumb, pagination) + single article (related sidebar, gallery, attachments, filters) at `/{locale}/news[/{slug}]`
- [x] Generic public page renderer: `Public\PageController@show` at `/{locale}/pages/{slug}` (current-locale slug, sanitised content, SEO meta, 404) + `navPages` shared prop → footer section links; CMS-managed content backbone for the static sections below
- [x] RSS feed for news — `Public\FeedController@news` at `/{locale}/news/rss` (RSS 2.0, per-locale, discovery `<link>` in head)
- [~] "About the Committee" section — content page + **leadership / structure / vacancies** sub-pages live (dedicated modules — see Phase 21); **regional offices** only inside Contacts, and **history / partners / anti-corruption** have no route/page/seed yet
- [~] "Activities" section — single seeded CMS content page only; **detailed sub-pages not implemented** (no child routes/pages/seeds)
- [x] "Operational situation" section — incidents archive headed by a status summary (active / controlled / resolved counts) + «Открыть карту» link; active warnings via the site alert banner
- [x] Safety guides catalog by hazard type + guide page — full `Guide`/`GuideTranslation` module: CMS CRUD (`guides.manage`, RichText content, downloadable files on private disk, trash, tj/ru/en badges) + public catalogue `/{locale}/guides` (audience filter) and guide page with downloads + print
- [x] Educational / children materials sub-section — `GuideAudience` (general / children); catalogue audience filter surfaces children's guides (§6.5)
- [x] Contacts section — dedicated `Public\ContactController` at `/{locale}/contacts`: emergency-numbers grid (112/101/102/103), regional offices on a MapLibre map, feedback CTA → e-приёмная (§6.9)
- [x] Open Graph / social preview meta (server-rendered og:* + Twitter card + per-locale og:locale, emblem image) + print stylesheets (`@media print` + `print:hidden` chrome, guide print button) (§6.12)
- [x] Branded error pages (403/404/419/429/500/503) via Inertia `respond()` → `public/error` with nav + emergency phone; localized, skipped locally for the debug page (§6.12)
- [x] Tests: page renderer, RSS, error pages, guides, contacts regions, operational-situation summary, news, homepage

## Phase 9 — Documents Registry

- [x] `documents` + `document_translations` (type, date, source, name/description per locale, soft-delete) + medialibrary `files` collection on the private `local` disk (§6.8)
- [x] Document categories via `DocumentType` enum (laws, regulations, departmental, plans, reports, forms) (§6.8)
- [x] Document CMS CRUD + trash with multi-file upload, mime/size validation (no executables), private storage (§6.8, §12.4)
- [x] Public registry: search + type filter + file size/name + date-range filter (§6.8)
- [x] Controlled file download route (files on private disk, streamed via route, 404 for draft) (§12.4)
- [x] Tests: document CRUD, upload, executable rejection, controlled download + draft 404, soft-delete

## Phase 10 — Appeals

- [x] `appeals` table (reference, category, contacts, subject, message, status, assignee, internal_note, soft-delete) (§6.7)
- [x] Public appeal form: category classifier, **honeypot anti-spam + `throttle:6,1`** rate limiting + file attachments (§6.7, §12.4)
- [x] Confirmation + registration number (`OBR-YYYY-XXXXXX`) shown on submit (§6.7)
- [x] CMS moderation queue: assignee, statuses, internal comments, deadline tracking (§6.7, §7.6)
- [x] Public status-tracking lookup by reference number (§6.7)
- [x] Personal-data access restricted to `appeals.manage` role (§12.5)
- [~] Register export (§7.6) — CSV export (`appeals/export`, BOM for Excel) works but filters **only by search+status — no date/period range** yet
- [x] Tests: submission, honeypot, validation, tracking, moderation update, authorization

## Phase 11 — Tourist Registration

- [x] `tourist_groups` table (leader/contacts, participants, route, equipment, dates, region, start coords, status, assignee, internal_note, soft-delete) (§6.6)
- [x] Public application form: leader/contacts, route, region, dates, participant count + honeypot + `throttle:6,1` (§6.6)
- [~] Region binding done + single start-point map picker added; a true **route/track (polyline) picker** is still pending (route remains a free-text field) (§6.6)
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
- [x] Web push: service worker, opt-in, topic/region selection, unsubscribe (§6.4.2)
- [x] Push delivery on alert publish (queued) (§6.4.2)
- [x] CMS: subscriber registry (search/status filter) + counts/stats (`subscribers.manage`) (§6.4.4)
- [x] Send preview + confirmation before bulk send; double-send protection — recipient-estimate endpoint (`alerts/estimate`) + confirmation dialog built; `notified_at` guard prevents re-send (§6.4.4)
- [x] Channel extensibility (SMS/messenger) — `notifications_log.channel` + topic model leave room (§6.4.4, §10.8)
- [x] Tests: double opt-in, unsubscribe, consent/topic validation, honeypot, re-subscribe, CMS registry

## Phase 13 — Multilingual System

- [x] Interface translation dictionaries (tg/ru/en) — `lang/{locale}/{ui,enums,mail}.php`: `ui` shared as `translations` prop + client `useTranslations` (`t()`, dot-keys, `:placeholder`, key fallback) — ALL 11 public pages + chrome + alert banner converted (~90 keys, zero hard-coded Cyrillic left); `enums` powers all 12 enum `label()` via `__()`; `mail` localizes both e-mail templates + subjects with per-subscriber `->locale()` (§14)
- [x] Language switcher on all pages; persist selection (session via SetLocale); first-visit browser detection (tg→tj) (§14)
- [x] Locale URL prefix + hreflang + canonical generation — `App\Support\LocaleUrls` (shared with switcher), server-rendered in app.blade.php (canonical + alternates + x-default, valid BCP-47 `<html lang>`); admin/auth routes emit none (§14, §15.1)
- [x] Missing-translation handling — fallback chain live everywhere; the «available in other language» banner **fixed 2026-07-07** (correct key `site.missing_translation` added to all 3 dictionaries, shows the content's native language name; rules-of-hooks fixed) (§14)
- [x] Per-material independent language publishing (translations optional per locale) + translation-status indicator in CMS — `locales` row field + tj/ru/en badge column («Языки») in all 6 module indexes (§7.9)
- [~] Locale-aware **date** formatting done (`Intl.DateTimeFormat` tg-TJ/ru-RU/en-US); **number formatting absent** (no `Intl.NumberFormat`/grouping helper) (§14)
- [x] Full Tajik Cyrillic support in fonts, search, forms, URLs/slugs (§14)
- [x] Tests: locale resolution (SetLocale), fallback (RegionTest), hreflang/canonical (SeoAlternatesTest), per-locale dictionaries + key parity ×3 groups, enum label locales, slug per language (content tests)

## Phase 14 — Search Engine

- [x] Full-text search over posts, documents, guides, pages (locale-aware) (§6.10)
- [~] MySQL full-text indexes (+ Scout abstraction if needed) (§10) — **FULLTEXT indexes** on 11 translation tables + `TranslationSearch` (`whereFullText` on MySQL, LIKE on SQLite tests); Scout still optional/deferred
- [x] Results page (§6.10) — **pagination** (20/page), **content-type filter chips**, **match highlighting** (`SearchHighlighter` + `<mark>`); API modal inherits highlights
- [x] Tests: per-type coverage + **Cyrillic (ru) queries**, **type-filter**, **pagination**, and **highlight** assertions (`SearchTest` + `SearchHighlighterTest`)

## Phase 15 — Analytics

- [x] Self-hosted Matomo integration (privacy-respecting tracking) (§15.2)
- [x] Goal tracking: subscriptions / tourist / appeals (§15.2) — `config/matomo.php` + shared `matomo` Inertia props + `useMatomoGoal` on subscribe (pending), appeals & tourist success screens; `trackGoal` when goal IDs set, `trackEvent` fallback otherwise — `MatomoGoalsTest`
- [x] sitemap.xml (with language versions) + robots.txt (§15.1)
- [x] schema.org markup (Organization, NewsArticle) (§15.1)
- [~] 301 redirect **mechanism** done & tested (`LegacyRedirects` middleware + `fallback`), but the legacy kchs.tj/khf.tj **URL map is empty** (`config/redirects.php` has only commented examples) — must be populated before launch (§15.1)
- [x] Social account links/widgets (data/perf-safe) (§6.12, §15.3) — `config/social.php` + env URLs; outbound icon links in public footer (`SocialLinks` component, no embeds); shared via Inertia — `SocialLinksTest`
- [x] Tests: sitemap generation, redirects, structured data presence

## Phase 16 — Security Hardening

- [x] HTTPS-only + HTTP→HTTPS redirect + HSTS (§12.1) — `URL::forceScheme('https')` in production (AppServiceProvider) + HSTS emitted by `SecurityHeaders` middleware (config-driven max-age/subdomains/preload); secure cookies on
- [x] CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy headers verified (§12.2) — `SecurityHeaders` middleware + `config/security.php` (CSP directives, frame/referrer/permissions policy, `X-Permitted-Cross-Domain-Policies`); dev CSP widened for Vite HMR; covered by `SecurityHeadersTest`
- [x] HTML sanitization for WYSIWYG output (XSS) (§12.2) — stevebauman/purify on translation bodies; covered by `SecurityTest` + `PostManagementTest`
- [x] CSRF verified on all mutating ops; SQLi covered by Eloquent (§12.2) — Laravel `VerifyCsrfToken` on web + Eloquent parameter binding throughout
- [x] File upload hardening: type/size checks, no executables, storage outside webroot, controlled routes (§12.4) — `SafeFileUpload` rule (rejects executables), documents/guides on the private `local` disk via controlled download routes; covered by `SecurityTest` + `DocumentManagementTest`
- [x] Anti-spam + rate limiting on all public forms (§12.4) — honeypot on appeals/tourist-groups/subscribe request classes + `throttle:` on every public POST and tracking lookup; login/2FA/passkey rate limiters in Fortify
- [x] Password policy, secure sessions (cookie flags, idle timeout) (§12.3) — `Password::defaults` min 12 + mixed case/letters/numbers/symbols (+ uncompromised in prod); session `secure`/`http_only`/`same_site=lax`, 120-min lifetime
- [x] Audit log: who/what/when for CMS actions + security events; tamper-resistant (§7.10, §12.7) — spatie/activitylog (`activity_log`). **Content auditing on all 8 CMS models** (posts, pages, categories, incidents, alerts, documents, guides, users — added Alert/Category/Guide/Page). **Security events** (login / logout / failed-login / lockout / 2FA enable·confirm·disable) recorded with IP + user-agent via `LogAuthenticationActivity` subscriber. Read-only CP viewer (`admin/audit-logs`) with event/type/search filters, gated by `audit.view`; append-only (no mutate routes) = tamper-resistant. Covered by `AuditLogTest`
- [x] Secrets outside repo; dependency vulnerability checks (§12.5, §12.6) — secrets in `.env` (git-ignored); CI `security.yml` runs `composer audit` (blocking — currently clean) + `npm audit --omit=dev` (report-only until the dev-tool advisory `concurrently → shell-quote` is moved to devDependencies, flagged separately) on push/PR + weekly
- [ ] Security review pass (OWASP Top 10) (§18.1)
- [x] Tests: headers, sanitization, upload rejection, rate limits, audit entries — `SecurityHeadersTest` (headers), `SecurityTest` (sanitization + upload rejection + activity), `AuthenticationTest` (login throttle), `AuditLogTest` (content + security audit entries, viewer access/filter)

## Phase 17 — Performance Optimization

- [x] Database/file page+data caching with correct invalidation on publish (alerts never cache-delayed) (§10.6, §13.1; no Redis per D-10)
- [x] HTTP cache headers + versioned static assets (§13.1)
- [x] Image optimization + responsive/adaptive delivery + lazy loading (§13.1)
- [x] Code splitting + response compression (gzip/brotli) (§13.1)
- [x] DB query optimization: indexes, eliminate N+1 (eager loading), paginate large lists (§13.1)
- [x] Move heavy ops (sends, import, image processing) to queues (§10.4)
- [~] Load testing (§13, §18.1) — an in-process `app:benchmark` command checks TTFB≤600ms at configurable concurrency, but **no captured results proving ×10 peak** and no automated assertion
- [x] Graceful degradation under load (secondary features limited, alerts/ops preserved) (§13)

## Phase 18 — Testing

- [x] Backend unit + feature test coverage of key logic (§18.1) — 245 Pest tests across auth/RBAC/2FA, all CMS modules (CRUD + render + permission gating), public portal, i18n/SEO, security (headers/sanitization/uploads/audit), and the internal API
- [ ] Key client-side scenario tests (Pest browser / smoke) (§18.1)
- [~] Accessibility audit WCAG 2.1 AA + low-vision mode verification (§6.11, §11.7, §18.1) — **public-surface WCAG sweep done** (two-reviewer audit + remediation): skip-to-content link + focusable `<main>` (2.4.1), labelled landmarks on every `<nav>` incl. the mobile bottom bar as a real `<nav>` with `aria-current` (1.3.1/4.1.2), the emergency hero de-aliased from `role="alert"` (4.1.3), global search input named + `aria-live` results region (4.1.2/4.1.3), media-library/document filters labelled (1.3.1), subscribe topics → `<fieldset>/<legend>` + live status banner (1.3.1/4.1.3), and **form error association** (`aria-invalid` + `aria-describedby` + error ids) across appeals/tourist-groups/subscribe (3.3.1); HazardBadge de-aliased from `role="status"`; decorative icons hidden. Low-vision mode verified (below). Remaining: formal contrast-ratio measurement, residual decorative-icon polish on a few content pages, and a CMS-side pass
- [ ] Cross-browser + mobile responsiveness checks (§18.1)
- [x] Internal API (token auth, versioning, rate limit) + documentation (§10.9, §18.3) — dependency-free token API: `routes/api.php` versioned under `/api/v1`, locale-aware via `?locale=` (`SetApiLocale`), `throttle:api` 60/min keyed by token; `Authorization: Bearer` auth (`AuthenticateApiToken` + `api_tokens` SHA-256-hashed store, minted with `api:token`); read endpoints alerts/incidents/news(+show) via Eloquent API Resources (active/published only, body only on show); open self-documenting `/api/v1` discovery endpoint; JSON errors. Covered by `Api/V1/ApiTest` (12 cases)
- [x] Low-vision accessibility mode (font size, contrast, reduced graphics, keyboard nav, screen-reader) (§6.11) — `accessibility-toolbar` in the public layout: font size (normal/large/xl), 4 contrast schemes (normal/monochrome/inverted/blue-yellow), image modes (on/grayscale/off), reset; persisted to localStorage, applied via `html.a11y-*` classes in `app.css`, fully trilingual (`a11y.*` keys)
- [~] UAT support on staging (§18.1) — checklist in `DEPLOY.md` §9; needs staging host + secrets configured

## Phase 19 — Deployment

- [x] Shared-hosting deploy guide: docroot → `public/`, SSH deploy + `migrate --force`, asset build upload (D-13; §16.1) — **`DEPLOY.md`** covers §3 (symlink + no-symlink docroot, pre-built assets, `.env`, cron, `migrate --force` + `optimize`). NB: fix its §5 admin-create snippet (uses a non-existent `role` column instead of spatie `assignRole`)
- [x] Single cron entry `* * * * * php artisan schedule:run`; scheduler drains DB queue via `queue:work --stop-when-empty` (D-10; replaces Supervisor) — `routes/console.php` schedules `queue:work --stop-when-empty --tries=3 --max-time=55` every minute (`withoutOverlapping`) + weekly `activitylog:clean`; covered by `SchedulerTest`
- [~] Optional Docker/Compose for local dev only — stock Laravel Sail `compose.yaml` (app + mysql:8.4 + mailpit) documented in `DEPLOY.md` §2; not the tailored Nginx/PHP-FPM split, but sufficient for local dev (D-13)
- [~] CI/CD pipeline: lint, types, tests, Vite build, deploy (extend existing GH Actions) (§16.2) — `deploy.yml` added: manual dispatch, `deploy:env-check`, release artifact; SSH auto-deploy commented until `SSH_*` secrets are set
- [x] Env separation: dev / staging / production; secrets management (§16.1) — `.env.staging.example` + `.env.production.example`, VAPID vars in `.env.example`, `config/deployment.php`, `deploy:env-check`, `env:encrypt` documented in `DEPLOY.md`
- [→] Backup automation + restore verification (§4.3, §16.3) — **deferred (owner 2026-07-07, D-24):** shared host provides its own backups. Revisit at UAT: confirm 30-day retention, media (`storage/app`) coverage, and run a restore drill (RTO≤4h/RPO≤24h still applies)
- [x] Monitoring/alerting (availability, errors, queues) (§4.3, §16.3) — `GET /health` (public summary + token-gated DB/cache/queue diagnostics via `HealthReporter`), `HEALTH_FAILED_JOBS_THRESHOLD`, `/up` unchanged; `HealthTest`
- [~] TLS certificate management (§16.3) — host-panel AutoSSL/Let's Encrypt documented in `DEPLOY.md` §8; `URL::forceScheme('https')` for staging+production; no in-app cert automation (shared hosting)
- [~] Deploy/runbook (RU) present (`DEPLOY.md`: high-load mode, admin account, backup note) + API self-documents (`/api/v1`); **architecture doc + comprehensive admin/user (RU) manual still missing** (§18.3)

## Phase 20 — Statamic-style Control Panel (CMS redesign, D-19)

> Re-skin the CMS to a faithful "lux copy" of the Statamic Control Panel on top of the existing DB
> backend (D-19): keep all Eloquent/RBAC/data, replicate Statamic's CP architecture concepts +
> design/UX, on the КЧС brand. Built incrementally; each module's existing CRUD is preserved.

- [x] CP shell — Statamic-style chrome: light grouped sidebar (uppercase section labels, accent-tinted active item, brand block, account menu at foot), slim global header (breadcrumbs + view-site), soft neutral page background, mobile drawer; replaces the shadcn AdminLayout internals (`admin-layout`, `admin-sidebar`, `cp-topbar`, `cp-user-menu`)
- [~] CP design tokens pass — the CP token set is now documented inline in `app.css` (typography/radii/shadows/backgrounds); a deliberate scale-alignment re-tuning may still be desired (still КЧС palette)
- [x] Listing component (Statamic "Listing") — `DataTable` restyled in place (white bordered card, uppercase header, hover rows, search on `bg-card`, polished pagination); API unchanged so all 13 module indexes upgraded at once. Column-toggle + bulk actions deferred
- [x] Publish form (Statamic two-column) — reusable shell (`cp/publish-form`: `CpPublishForm` two-column + Save/Cancel header, `CpPanel` field group, `CpLocaleTabs` locale switcher, large borderless title). **All 7 module forms converted** (posts, pages, categories, incidents, alerts, documents, guides): localised fields in the main column, meta in the sticky sidebar (status/type/category/dates/files/map). `useForm` shapes preserved → controllers/tests unaffected. Revisions/site-switcher deferred
- [x] Fieldtypes — `cp/fields.tsx`: `CpField` shell (label + instructions + control + error) + `CpTextField` (incl. date/datetime via `type`), `CpTextareaField`, `CpSelectField`, `CpToggleField`, `CpRichTextField` (bard — TipTap wrapped in the field shell, remounts per-locale via `editorKey`); plus `CpRelationField` (relationship picker via a stack) and **`CpAssetsField`** (`cp/assets-field.tsx` — single-image assets fieldtype: upload **or** pick from the media library in a stack). Showcased on the **alerts** form (select/relation/datetime/toggle/textarea); posts «Рубрика» uses the relation field; **bard adopted in posts/pages/guides**; **assets adopted on the posts cover** (`cover_media_id` contract, server-side copy — D-22). Remaining forms can migrate incrementally
- [x] Stacks — `cp/stack.tsx` (`CpStack`) slide-over on the Radix Sheet (right-anchored, overlay, focus-trap, Esc, slide animation, layerable); first real usage = the relationship picker in `CpRelationField`
- [x] Command palette (⌘K) — `command-palette.tsx` on Radix Dialog (no new deps): fuzzy-jump to any section + «Создать …» actions, permission-gated from the shared `admin/nav.ts`, fully keyboard-driven (↑/↓/Enter/Esc); trigger in the CP topbar. Nav data extracted to `components/admin/nav.ts` (shared by sidebar + palette)
- [x] Dashboard widgets (Statamic-style cards) — the operational dashboard (attention banner, KPI widget cards, recent appeals/incidents panels, system stats; all permission-gated) was already built; polished the KPI widgets with tinted icon chips to cohere with the CP look
- [x] Dark mode parity + a11y pass for the whole CP — every CP surface is built on semantic tokens (`bg-card`/`text-foreground`/`border-border`/`bg-primary/10`…) so dark mode adapts automatically; added `focus-visible:ring-2 focus-visible:ring-ring` rings to every interactive CP element (sidebar nav, topbar buttons, account menu, command-palette trigger + items, locale tabs, relation-field trigger/clear, borderless titles), plus `sr-only` dialog/sheet descriptions for SR users
- [x] Tests: CP renders per module, nav permission-gating, publish-form save flow — covered by the per-module feature suite (every `*ManagementTest` asserts the Inertia component + props render, store/update save flow, and route-level permission gating via `assertForbidden`). The Phase 20 redesign was presentation-only with `useForm`/route shapes preserved, so these tests cover it; added create+edit form-screen render assertions to the two thin modules (documents, guides) so all 7 publish-form modules assert their redesigned form renders. Client-side nav gating rides on the tested server-side gate (D-16)

## Phase 21 — §20 Mandatory Section Modules (retro-documented 2026-07-07)

> These §20-mandated sections were built in code **outside** the original 20-phase plan and had no
> checkbox. The 2026-07-07 audit verified each is genuinely complete (model + translations + migrations
> + admin CRUD with Form Requests + public read + Inertia pages + factories + passing Pest tests) and
> wired into the CMS sidebar, ⌘K palette, global search and `sitemap.xml`. Marked `[x]` to reflect reality.

- [x] **Leadership** (ТЗ §20«г» — руководство + график приёма): `Leader`/`LeaderTranslation` (status, sort, `photo` media collection, reception schedule) + admin CRUD (`leadership.manage`) + public `/{locale}/leadership` + `LeadershipManagementTest`/`LeadershipStructureTest`
- [x] **Structure** (ТЗ §20«б» — структурные подразделения): `Subdivision`/`SubdivisionTranslation` (self-nesting `parent_id` tree, `staff_count`, functions) + admin CRUD (`structure.manage`, self-parent guard) + public `/{locale}/structure` (recursive tree) + `StructureManagementTest`
- [x] **Gallery** (ТЗ §20«ш» — фотогалереи): `Gallery`/`GalleryTranslation` (medialibrary `photos` + thumb) + admin CRUD (`gallery.manage`) + public index/show (per-locale slug) + `GalleryManagementTest`/`GalleryFaqTest`. NB: hard-delete (no trash)
- [x] **FAQ** (ТЗ §20«й» — вопросы/ответы): `Faq`/`FaqTranslation` (Purify-sanitized answers) + admin CRUD (`faqs.manage`) + public `/{locale}/faq` + `FaqManagementTest`/`GalleryFaqTest`. NB: hard-delete (no trash)
- [x] **Statistics** (ТЗ §20«у» — ключевые показатели): `Statistic`/`StatisticTranslation` (value/year, KPI cards) + admin CRUD (`statistics.manage`) + public `/{locale}/statistics` + `StatisticManagementTest`/`StatisticsTest`. NB: distinct from the homepage operational counters (those come from Incident counts)
- [x] **Vacancies** (ТЗ §20«н», §21 — вакансии + онлайн-анкета): `Vacancy`/`VacancyTranslation`/`VacancyApplication` (soft-delete, `EmploymentType`, private-disk résumé, `VAC-YYYY-XXXXXX`) + admin CRUD + applications moderation queue (private download) + public listing/show (JobPosting schema.org)/apply (honeypot + MIME allowlist)/track + `VacancyManagementTest`/`VacancyTest`
- [x] **Tenders** (ТЗ §9, §20«э» — закупки/торговая площадка): `Tender`/`TenderTranslation`/`TenderBid` (soft-delete, `TenderType`, budget, private-disk doc, `TND-YYYY-XXXXXX`) + admin CRUD + bids moderation queue (private download) + public listing/show/bid/track + `TenderManagementTest`/`TenderTest`
- [~] Parity nicety: **no register/period export** for the vacancy-applications and tender-bids queues (appeals has one under §7.6); applicant/bidder receive only an on-screen reference (no emailed receipt). Optional, not a blocker
- [ ] **Polls / Опросы** (ТЗ §8 + §20«к», incl. anti-corruption expertise of draft acts) — no model/migration/route exists yet
- [ ] **Services / Услуги** (ТЗ §20«ф» — government-services catalogue) — not built (could start Page-based)

---

## Decision Log

> Architectural decisions. Status: **Proposed** until confirmed by the project owner.

- **D-24 (Accepted 2026-07-07, owner):** **Backups delegated to the shared host.** The owner considers the hosting provider's own backups sufficient for now, so app-level backup automation (§16.3) is deferred (`[→]`). Revisit before launch/UAT: confirm the host's retention meets the ТЗ **30-day** window, that uploaded media under `storage/app` is covered, and run a **restore-verification** drill against §4.3 (RTO≤4h/RPO≤24h).
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
- **D-19 (Accepted 2026-06-11, owner):** CMS UX/UI modelled on **Statamic Control Panel**. Owner
  wants the CMS to look and feel like a "lux copy" of Statamic. Two scoping decisions taken with the
  owner: (1) **keep the existing database backend** (Eloquent/RBAC/translations + dynamic & personal
  data: incidents/alerts/appeals/subscribers/audit) — Statamic's flat-file architecture is a poor
  fit for a gov emergency portal; we replicate Statamic's CP **architecture concepts + design/UX**
  (grouped nav, listings, two-column publish forms, fieldtypes, Stacks/slide-overs, command palette)
  1:1 on top of it. (2) **Statamic layout + КЧС brand**: faithful Statamic CP structure/components/
  interactions, but on the КЧС palette (primary `#1f4e8c`, Inter) and WCAG-accessible — not a
  pixel-copy of Statamic's own colours. Built incrementally as Phase 20.
- **D-22 (Accepted 2026-06-15):** The assets fieldtype keeps the post **cover as a spatie media
  collection on the model** (not a foreign-key/URL field). A library pick is passed as `cover_media_id`
  and the controller **copies** the chosen `MediaFile`'s file into the cover collection via spatie
  `Media::copy()`. Rationale: preserves the existing cover storage/conversions and the upload path
  (backward-compatible), avoids an HTTP self-fetch (`addMediaFromUrl`) so it's testable with faked
  storage, and a copy keeps the cover independent of later edits/deletes to the library asset. The
  same field generalises to other single-image fields later.
- **D-21 (Accepted 2026-06-15):** The internal API uses a **custom, dependency-free bearer-token**
  scheme (an `api_tokens` table of SHA-256 hashes + a thin middleware), **not Laravel Sanctum**.
  Rationale: CLAUDE.md forbids adding dependencies without owner approval, and the requirement is a
  small read-only server-to-server API where static hashed tokens (minted via `api:token`) fully
  satisfy "token auth" without the weight of Sanctum's personal-access-token/SPA machinery. If the
  portal later needs per-user OAuth-style scopes or SPA cookie auth, revisit and adopt Sanctum then.
- **D-20 (Accepted 2026-06-15):** The audit-log viewer is gated by the purpose-built **`audit.view`**
  permission (granted to both super-admin and moderator as "read-only system insight"), not
  `settings.manage`. Rationale: the route originally used `settings.manage` (super-admin only), which
  left `ViewAudit` as dead code and contradicted the permission enum's documented intent. The viewer
  is strictly read-only and the log is append-only, so giving moderators read access to the security
  trail is low-risk and matches least-privilege insight. Reversible by switching the one `can:` middleware.
- **D-23 (Accepted 2026-06-15, owner):** Public-portal visual direction = **modern govtech**
  (FEMA/USWDS-style, emergency-forward). Three forks confirmed with the owner: (1) brand colour moved
  to the **official navy `#1F4E8C`** (the ТЗ Приложение В primary; the tokens had drifted to a brighter
  `#1e40af`), signal orange reserved for CTAs; new `--brand` + deep-navy `--brand-strong` (`#0f2f5e`)
  chrome tokens drive the gov bar, hero and footer. (2) Added an **official government identifier bar**
  (`GovBar`) above the masthead — the canonical govtech trust signal. (3) The auto-rotating homepage
  **carousel is retired** (a11y anti-pattern) for a **static hero with a live operational-status
  indicator** (`GovHero` — status derived from active alerts; incident counts from a new `operational`
  prop). Header/footer re-tokenised off hardcoded `slate-*`; footer rebuilt as a structured block
  (agency identity, sections, emergency numbers, resources, legal/WCAG bar). `home-slider.tsx` is now
  unused. Presentation + one read-only controller prop; all 251 tests green.

## Change Log

- **2026-07-07** — **Menu per-language visibility (§7.8, item 157).** `MenuFormatter` service hides
  items (and children) without a title for the active locale — primary + footer menus; no blank labels.
  CMS: default-locale title required, other locales optional; clearing a title removes that translation.
  Admin builder shows locale badges per item. `MenuVisibilityTest` (4) + `MenuManagementTest` extended.
  Item 157 → `[x]`. **395 tests green** (+6).
- **2026-07-07** — **Search §6.10 (Phase 14).** MySQL FULLTEXT indexes on 11 translation tables;
  `TranslationSearch` helper (FULLTEXT on MySQL, LIKE fallback in SQLite tests); `SearchHighlighter`
  for safe `<mark>` highlighting; results page: pagination (20/page), content-type filter chips, highlighted
  title/excerpt; API modal inherits highlights. `SearchContentType` enum. Items 269–270 → `[x]`; 268 → `[~]`
  (Scout still optional). **389 tests green** (+6). pint clean.
- **2026-07-07** — **Tier-0 launch-ops (env / monitoring / CI deploy).** Env separation: `.env.staging.example`,
  `.env.production.example`, VAPID keys in `.env.example`, published `config/webpush.php`, `config/deployment.php`,
  `php artisan deploy:env-check` (validates staging/production secrets via config, not raw env()), `env:encrypt`
  documented. Monitoring: `GET /health` + `HealthReporter` (DB/cache/queue + failed-jobs threshold),
  `HEALTH_CHECK_TOKEN`. CI: `.github/workflows/deploy.yml` (manual dispatch → build → env-check → artifact).
  `DEPLOY.md` updated (§3.3 env/VAPID/health, fixed §5 admin `assignRole`, §7 CI, §8 TLS, §9 UAT checklist).
  `AppServiceProvider`: HTTPS forced on staging too. Items 326 + 328 → `[x]`; 325/329/318 → `[~]`.
  **383 tests green** (+8). pint clean.
- **2026-07-07** — **§7.3 block page-builder + homepage composer (items 155–156).** Added the 4 missing
  block types (`image_gallery`, `accordion`, `table`, `contacts`) to `blocks-field.tsx` and
  `block-renderer.tsx`; replaced the `map_widget` text placeholder with real `MapView` (lat/lng/zoom/title
  from block data). New `App\Support\BlockSanitizer` wired into `PageController` — HTML fields via
  `HtmlSanitizer`, plain-text fields stripped, `javascript:` URLs neutralised. `PageBlocksTest`: save all
  8 types, XSS sanitisation, public page render, homepage `is_home` blocks. Items 155 + 156 → `[x]`.
  **375 tests green** (371 + 4). pint / eslint clean.
- **2026-07-07** — **Fixed 2 reconciliation-surfaced bugs (option A).** (1) **Map popup** (§6.3, item 187):
  the click popup rendered only the title — `map.tsx` packs type/level/status/region/datetime into a
  `lines[]` array (which MapLibre JSON-serialises) but the popup read `props.type/level/region`
  (undefined). Rewrote the popup to parse `lines[]` and render each already-localised field
  (XSS-safe `textContent`); strengthened `MapTest` to assert the payload contract. (2)
  **Missing-translation banner** (§14, item 263): `MissingTranslationAlert` called a non-existent key
  `ui.site.missing_translation` and rendered the literal string; fixed to `site.missing_translation`,
  added the key to all 3 dictionaries (`:language` = content's native name), and moved the
  `useTranslations()` hook above the early return (rules-of-hooks). Also fixed a **case-sensitivity
  bug** that would break these pages on Linux prod — 5 show pages imported
  `@/components/public/missing-translation-alert` (lowercase) while the file is `Public/`. Items 187 +
  263 → `[x]`. **371 tests green** (had to regenerate the Vite manifest via `npm install` + `npm run
  build` — a concurrent branch change had added the menus module + `@radix-ui/react-tabs` without
  installing it, which is unrelated to these fixes). pint / eslint(touched) / prettier clean.
- **2026-07-07** — **Plan↔code reconciliation (10-agent audit; no application code changed).** The
  checklist body had drifted *ahead* of the code (bulk-marked `[x]`), while the header still read
  "Phase 3 / 68%". Ran the 342-green suite, then a 10-reader workflow verified every checkbox and every
  undocumented module against actual models/migrations/controllers/pages/tests. **Corrections: 20
  over-claims** — `[x]→[~]` on block builder (155, 4/8 types), homepage blocks (156), menu editor (157,
  no per-lang visibility), content tests (159, no scheduling test), map popup (187, title-only bug),
  map layers (188), tile config (192), About sub-pages (202), Activities sub-pages (203), appeals
  register export (229, no period filter), tourist track picker (236), missing-translation note (263,
  broken key), locale number formatting (265), search results page (273), search tests (274), legacy
  301 map (282, empty), load testing (308); and `[x]→[ ]` on MySQL FULLTEXT (272), Matomo goals (279),
  social links (283). **2 genuine upgrades** `[~]→[x]`: incident CMS map-picker (166) + public-archive
  filters (169). **4 deploy upgrades** off the new `DEPLOY.md`: guide (323 `[ ]→[x]`), Docker (325),
  docs/runbook (331), CP tokens (340) → `[~]`. **Added Phase 21** documenting 7 §20 modules that lived
  only in code (Leadership, Structure, Gallery, FAQ, Statistics, Vacancies, Tenders — all complete +
  tested). Rewrote Progress Summary, Current Sprint, Next Action. Confirmed really-built-but-untracked:
  web push (VAPID keys still needed for prod), revisions/rollback, appeal attachments, send-preview
  dialog. **342 tests green throughout; plan.md only.**
- **2026-06-15** — **Public portal — modern-govtech redesign (D-23).** Re-skinned the citizen-facing
  portal to a FEMA/USWDS-style govtech standard. **Tokens:** `--primary` → official navy **#1F4E8C**
  (was #1e40af) across primary/ring/secondary-/accent-foreground/chart-1, plus new `--brand` and
  deep-navy `--brand-strong` chrome tokens (light + dark). **New `GovBar`** official identifier strip
  above the masthead; **new `GovHero`** static hero with a live operational-status pill (from active
  alerts) replacing the auto-rotating `HomeSlider`; homepage "quick links" became a real **task grid**
  of links; **footer** rebuilt as a structured navy block (agency identity + sections + emergency
  numbers 112/101/102/103 + resources + WCAG/legal bar); **header** re-tokenised off `slate-*`.
  **Backend:** `HomeController` now shares a cached `operational` incident-count summary; fixed the
  schema.org emergency `telephone` 119 → 112. **i18n:** added `govbar.*`, `home.hero` CTA + `home.status`
  + `home.operational`, `footer.accessibility|rights` to all three locale dictionaries (228 keys,
  parity-tested). Added `Public/HomeTest`. **251 tests green**; Pint/ESLint/types/build clean.
- **2026-06-15** — **Phase 18 — WCAG 2.1 AA sweep (public surface).** Ran a two-reviewer audit
  (layout/components + pages) then remediated every HIGH/MEDIUM finding: **skip-to-content** link +
  focusable `<main id="main-content">` (2.4.1); `aria-label` on all navigation landmarks and the
  mobile bottom bar converted from `<div>` to a real `<nav>` with `aria-current` (1.3.1, 4.1.2);
  `EmergencyHero` stripped of its static `role="alert"`/`aria-live="assertive"` (was duplicating
  `AlertBanner`) and region-labelled by its `<h1>` (4.1.3); `GlobalSearchModal` input given an
  accessible name + a polite `aria-live` results region with a count, spinner/icons hidden (4.1.2,
  4.1.3); document search/type filters labelled (1.3.1); subscribe topics wrapped in
  `<fieldset>/<legend>` and its push/status banner made a live region (1.3.1, 4.1.3); **form error
  association** wired across appeals (6), tourist-groups (9) and subscribe (`aria-invalid` +
  `aria-describedby` + `id` on `InputError`) (3.3.1); `HazardBadge` de-aliased from `role="status"`;
  decorative icons `aria-hidden`. Added 5 i18n keys (`a11y.skip_to_content`/`primary_nav`/`footer_nav`/
  `menu`/`search_results`) across tj/ru/en (key-parity test green). **249 tests green**; pint/eslint/
  types/build clean. Remaining: contrast-ratio measurement, residual decorative-icon polish, CMS pass.
- **2026-06-15** — **Phase 19 — CI/CD hardening + cron scheduler.** Added the shared-hosting
  scheduler (D-10): `routes/console.php` now drains the DB queue every minute
  (`queue:work --stop-when-empty --tries=3 --max-time=55`, `withoutOverlapping`) and prunes the audit
  log weekly (`activitylog:clean`) — driven by the single `schedule:run` cron; covered by
  `SchedulerTest`. Strengthened CI: **`types:check`** added to `tests.yml` (after the Vite build, so
  the git-ignored Wayfinder route/action types exist), and a new **`security.yml`** runs
  `composer audit` (blocking, clean) + `npm audit --omit=dev` (report-only) on push/PR + weekly —
  closing Phase 16's dependency-scanning item. `composer audit` is clean; the only npm advisory is a
  dev-tool transitive (`concurrently → shell-quote`) miscategorised in `dependencies`, flagged as a
  separate task (move to devDependencies + `npm audit fix`) rather than changing the lockfile
  unprompted. **249 tests green**; Pint clean, workflows YAML-validated. Deliberately avoided a
  full-repo Pint/Prettier reformat (45 pre-existing style-debt files) to not churn active WIP.
- **2026-06-15** — **Phase 20 — Assets fieldtype.** Built the Statamic-style media picker
  `CpAssetsField` (`cp/assets-field.tsx`): a single-image fieldtype that previews the current image
  and lets the editor either upload a new file or pick an existing asset from the media library,
  shown in a `CpStack` (grid + search, fetched from `/admin/api/media`). Adopted it on the **posts
  cover** field. Contract (D-22): the cover stays a spatie media collection on `Post`; a library pick
  sends `cover_media_id`, which `PostController::syncCover` copies into the cover collection via
  spatie `Media::copy()` (no HTTP fetch → testable; a fresh upload still wins; remove still clears).
  Added the `cover_media_id` validation rule (inherited by update) and two `PostManagementTest`
  cases (library-cover attach + invalid-id rejection). **247 tests green**; pint/types/lint/build
  clean. Phase 20 fieldtypes now complete; only the CP design-token doc remains.
- **2026-06-15** — **Phase 18 — Internal API + a11y verified.** Built the internal read API (§10.9,
  §18.3) **dependency-free** (no Sanctum — D-21): `api_tokens` table with SHA-256-hashed bearer
  tokens (plaintext shown once, minted via the new `api:token` command), `AuthenticateApiToken` +
  `SetApiLocale` middleware, `routes/api.php` registered in `bootstrap/app.php`, versioned `/api/v1`
  with a `throttle:api` 60/min limiter (keyed by token). Endpoints: open `/api/v1` discovery, plus
  token-gated `alerts` / `incidents` (paginated) / `news` (paginated) / `news/{id}` — all via Eloquent
  API Resources, exposing only active/published content, locale-aware (`?locale=`), full body only on
  show. Errors render as JSON (already configured). Added `Api/V1/ApiTest` (12 cases: auth/expiry,
  per-endpoint shape, draft exclusion, locale switch, rate-limit headers, command). Also **verified
  the low-vision accessibility mode** (`accessibility-toolbar`: font size / 4 contrast schemes / image
  modes, persisted, trilingual, `html.a11y-*` CSS) and marked backend test coverage done (**245
  tests**). Pint/types/build clean.
- **2026-06-15** — **Phase 16 — Security Hardening: completed the audit log; verified the rest.** On
  audit: most of Phase 16 was already built but unchecked (security headers + HSTS via the
  `SecurityHeaders` middleware/`config/security.php`, rate-limiting + honeypot on public forms, purify
  sanitization, `SafeFileUpload` hardening, strong password policy, secure session cookies) — marked
  those done with their proving tests. The one real gap was the **audit log**, which I finished:
  (1) extended spatie/activitylog coverage to **all 8 CMS models** (added `LogsActivity` to Alert,
  Category, Guide, Page — Post/Document/User/Incident already had it); (2) added a
  `LogAuthenticationActivity` event subscriber recording **security events** (login / logout /
  failed-login / lockout / 2FA enable·confirm·disable) with IP + user-agent (confirmed Fortify's
  custom `authenticateUsing` still fires `Login`/`Failed`); (3) rebuilt the stub `AuditLogController`
  into a real read-only viewer (humanised RU event/subject labels, event + type + search filters,
  shaped rows, `latest()` paginated) rendering the new CP page `admin/audit-logs/index` (fixes the old
  `Admin/AuditLogs/Index` case bug); (4) **re-gated the route from `settings.manage` → `audit.view`**
  (D-20) so the purpose-built permission is actually used (both roles get read-only insight); (5) added
  the «Журнал аудита» CP nav item; (6) added `AuditLogTest` (6 cases). Append-only + no mutate routes
  = tamper-resistant. **233 tests green**; pint/types/build clean, 0 lint errors.
- **2026-06-15** — Phase 20 cont.: **CP tests closed**. Verified the redesign is covered by the
  existing per-module feature suite — every `*ManagementTest` already asserts the Inertia component +
  props render, the store/update save flow, and route-level permission gating (`assertForbidden` for
  non-CMS users). Since Phase 20 was presentation-only (routes + `useForm` shapes preserved), that
  coverage holds; added create+edit form-screen render assertions to the two thin modules (documents,
  guides) so all 7 publish-form modules now assert their redesigned form renders. **227 tests green**;
  Pint clean. Phase 20 remaining: assets fieldtype (awaits a firmed media-reference contract) +
  CP design-token doc.
- **2026-06-15** — Phase 20 cont. (step-by-step «сделать всё»): **bard fieldtype + dark/a11y pass +
  Media fix closed**. (1) Added `CpRichTextField` to `cp/fields.tsx` — the TipTap `RichTextEditor`
  wrapped in the `CpField` shell, remounting per-locale via an `editorKey` prop; **adopted it in the
  posts, pages and guides content fields** (replacing the bare `<RichTextEditor>`), `useForm` shapes
  unchanged → controllers/tests untouched. (2) **Dark/a11y pass** for the whole CP: confirmed every
  surface rides semantic tokens (dark mode adapts for free) and added `focus-visible` rings across all
  CP interactive elements + `sr-only` descriptions on the stack/command-palette dialogs. (3) **Closed
  the Media-module NB** from the prior entry — lowercased the controller render + page path
  (`admin/media/index`) so it resolves on case-sensitive Linux, dropped its self-wrapped `AppShell`
  and put it on the CP `AdminLayout` via `.layout`, restyled to the CP card look. 225 tests green;
  lint/types/build clean.
- **2026-06-11** — Cleared the accumulated WIP type/lint errors → green gate (owner asked).
  `types:check` was red from the parallel media/rich-text build: `custom-image.ts` (typed the
  attributes record so the dynamic `delete`s are allowed), `Public/bottom-navigation.tsx` (dropped
  unused/missing imports incl. the non-existent `app-header-mobile-menu`, switched `t(key,'fallback')`
  → real `nav.*` keys, fixed the map route), `admin/Media/Index.tsx` (`Breadcrumbs` takes
  `breadcrumbs`/`href`, not `items`/`url`). Lint was red from the media-picker WIP:
  `media-library-modal.tsx` (removed unused `Input`, inlined the fetch-on-open effect to fix the
  use-before-declare + a `set-state-in-effect` disable for the loading spinner), `image-node-view.tsx`
  (dropped unused `useCallback`/`useEffect`, `isResizing` value), `image-crop-modal.tsx` (import
  order). **Now: types 0, lint 0 errors (1 pre-existing map-view warning), build ✓, 225 tests ✓.**
  NB still open: the Media page renders as `Admin/Media/Index` (capital) + self-wraps `AppShell` →
  it 404s on case-sensitive Linux and isn't on the CP layout; flagged for a deliberate fix.
- **2026-06-11** — Phase 20 cont.: **Dashboard widgets**. The CMS dashboard was already a strong
  Statamic-style widget board (attention banner + KPI cards + recent appeals/incidents + system
  stats, permission-gated) — polished the KPI widgets with tinted icon chips for cohesion with the
  CP. Presentation-only; controller props/logic untouched. Lint/types/build clean. **Phase 20 core
  is now in place** (shell, listing, publish forms ×7, command palette, stacks, fieldtypes,
  dashboard); remaining: assets fieldtype (needs the Media module), bard fieldtype, dark/a11y pass,
  CP tests.
- **2026-06-11** — Phase 20 cont.: **Fieldtypes**. `components/admin/cp/fields.tsx` — `CpField`
  shell (label + instructions + control + error) and the typed set `CpTextField` (text/date/datetime),
  `CpTextareaField`, `CpSelectField`, `CpToggleField`. Refactored the **alerts** form onto them as
  the showcase (select × hazard/status, `CpRelationField` for region via a stack, two datetime
  fields, dismissible toggle with instructions, body textarea) — `useForm` shape unchanged. Adds
  the Statamic "instructions under label" affordance. 225 tests green; lint/types/build clean.
- **2026-06-11** — Phase 20 cont.: **Stacks** + first **fieldtype**. `components/admin/cp/stack.tsx`
  (`CpStack`) — a Statamic-style right slide-over built on the Radix Sheet (overlay, focus-trap,
  Esc, slide animation; layerable). `components/admin/cp/relation-field.tsx` (`CpRelationField`) — a
  relationship picker fieldtype that opens a stack with a searchable list (clearable; value stays
  the related id|null). Wired into the posts «Рубрика» field, replacing the inline Select — `useForm`
  shape (`category_id`) unchanged, controller/tests untouched. Lint/types/build clean.
- **2026-06-11** — Phase 20 cont.: **Command palette (⌘K)**. New `components/admin/command-palette.tsx`
  on the existing Radix Dialog (no cmdk/new deps) — fuzzy search over all CMS sections + «Создать …»
  actions, permission-gated, keyboard-driven (↑/↓/Enter/Esc), trigger added to the CP topbar. Nav
  data extracted to a shared `components/admin/nav.ts` (consumed by both the sidebar and the palette;
  sidebar slimmed accordingly). Lint/types/build clean.
- **2026-06-11** — Fixed the WIP blockers that had turned the suite red (owner asked). (1) HTML
  sanitiser: `stevebauman/purify` config listed `display` in `CSS.AllowedProperties`, which
  HTMLPurifier rejects unless `CSS.AllowTricky` is on → enabled it in `config/purify.php` (keeps
  the rich-text image-alignment feature working, no fatal 512). (2) Dashboard: the stale
  `DashboardAccessTest` asserted flat `stats.users`; the controller + React page use the grouped
  `stats.system.*` shape → test updated to the real contract. (3) `spatie/laravel-responsecache`
  was active in tests, replaying cached plain responses that break Inertia assertions → added
  `RESPONSE_CACHE_ENABLED=false` to phpunit.xml. **Full suite green again: 225 passed, 1174
  assertions.** Pint clean.
- **2026-06-11** — Phase 20 cont.: **all module forms converted** to the Statamic two-column publish
  form. pages, categories, incidents (map + region→coords preserved), alerts, documents (files),
  guides (files) refactored onto `CpPublishForm`/`CpPanel`/`CpLocaleTabs` — localised fields + big
  borderless title in the main column, meta/files/map in the sticky sidebar. Every `useForm` shape
  kept identical (controllers untouched). The whole CMS now shares one Statamic-style listing +
  publish-form language. Lint/types/build clean for all converted files. (The parallel-WIP suite
  failures this surfaced — htmlpurifier `display`, dashboard, response cache — were fixed next; see
  the entry above.)
- **2026-06-11** — Phase 20 cont. (Statamic CP): **Listing** + **Publish form**. `DataTable` restyled
  to the Statamic listing (white bordered card, uppercase header, hover rows, refined pagination) —
  API unchanged, so all 13 module tables upgraded with zero per-page edits. New publish-form shell
  `components/admin/cp/publish-form.tsx` (`CpPublishForm` two-column + Save/Cancel header, `CpPanel`
  field group, `CpLocaleTabs` per-locale switcher with completion check, large borderless title);
  the **posts** form converted onto it as the flagship (localised fields in the main column, meta
  — status/type/category/date/cover — in the sticky sidebar). useForm shape preserved, so the
  controller/tests are unaffected. Lint/types/build clean for the new files.
- **2026-06-11** — Phase 20 started (Statamic-style CP, D-19): built the **CP shell**. Replaced the
  shadcn `AdminLayout` internals with a faithful Statamic Control-Panel chrome on КЧС tokens — a
  light grouped sidebar (`admin-sidebar`: brand block, uppercase section labels, accent-tinted
  active item, account menu pinned at the foot via new standalone `cp-user-menu`), a slim sticky
  global header (`cp-topbar`: breadcrumbs + «На сайт»), a soft neutral page background, and a mobile
  drawer. Dark mode preserved via existing tokens. CP shell files type/lint-clean; Vite build green.
  NB: `tsc` currently reports 3 errors in unrelated in-progress files (`pages/admin/Media/Index.tsx`,
  `components/Public/bottom-navigation.tsx`, `components/rich-text/custom-image.ts`) — not part of
  this change; flagged to the owner.
- **2026-06-11** — Phase 8 closed out (orchestrated via 2 workflows: 6-agent page build + 3-dim
  adversarial review). **Safety Guides** module (§6.5): `guides`/`guide_translations` migrations,
  `Guide`/`GuideTranslation` models, `GuideAudience` enum (general/children), `guides.view|manage`
  permissions (moderator + super-admin), admin CRUD (`GuideController` + Store/Update requests,
  RichText content sanitised on save, private-disk downloads, trash, tj/ru/en badges) and public
  `GuideController` (catalogue `/{locale}/guides` with audience filter; guide page `/{locale}/guides/{slug}`
  with downloads + print; controlled download). **Contacts** (§6.9): `ContactController` →
  `/{locale}/contacts` with emergency numbers, regional offices on a MapLibre map, feedback CTA.
  **Operational situation** (§5): status-count summary + map link on the incidents archive.
  **Print** (§6.12): `@media print` block + `print:hidden` chrome + guide print button. Dictionaries
  extended (ui.guides/contacts/incidents.summary + nav.guides/contacts ×3, enums.guide_audience ×3).
  Admin sidebar + public footer wired. Demo seeder seeds 4 guides. Adversarial review surfaced 5
  real issues, all fixed: (1) per-locale unique slug on `guide_translations` + `uniqueSlug()`
  dedup (Tajik titles that `Str::slug` empties no longer collide → no unreachable guide);
  (2) **hreflang + language switcher** now use each locale's OWN slug on slug detail pages
  (guides/pages/news .show) via `LocaleUrls::contentUrls()` → `localeSwitch`/`seoAlternates` props
  (previously swapped only the locale prefix → 404 on switch — also fixed the pre-existing news.show
  case); (3) incidents summary now matches the list (dropped the locale filter so active incidents
  are never hidden); (4) contacts feedback copy (`contacts.feedback_text`); (5) page «last updated»
  now rendered (`common.updated`). 14 new tests → 211 total. types/build/lint/Pint clean; live smoke
  (guides/contacts/incidents/pages 200, per-locale hreflang verified in HTML).
- **2026-06-10** — Phase 8 increment (content backbone, RSS, SEO, error pages): (1) generic public
  page renderer `Public\PageController@show` at `/{locale}/pages/{slug}` + `public/pages/show.tsx`
  (current-locale slug lookup, sanitised content, SEO prop, 404) — turns About/Activities/Contacts
  into CMS-managed content; `navPages` shared prop drives a footer «Разделы» link column.
  (2) `Public\FeedController@news` RSS 2.0 feed at `/{locale}/news/rss` (per-locale, `feeds.news`
  blade, discovery `<link>`). (3) Server-rendered Open Graph + Twitter card + `<meta description>`
  in app.blade.php from a `seo` page prop (emblem og:image, per-locale og:locale), title now from
  seo. (4) Branded error pages via `withExceptions(respond())` → `public/error.tsx` for
  403/404/419/429/500/503 (localized, nav + 112; skipped in `local` env for the debug page);
  `PublicLayout` hardened with defensive shared-prop defaults. New `ui.errors.*` + `footer.sections`
  keys ×3 locales. 8 new tests (Pages, NewsFeed, ErrorPage) → 197 total. types/build/lint/Pint clean.
- **2026-06-10** — Seeders: `AdminUserSeeder` (super-admin `aminjon1065@gmail.com` / `password10`
  + a moderator `moderator@khf.test`, idempotent via `updateOrCreate`; privileged roles still set
  up 2FA on first CMS visit per D-16) and `DemoContentSeeder` (trilingual demo: 3 categories,
  6 published posts, 2 pages, 4 incidents across hazard levels, 1 active alert, 4 documents with
  attached PDFs, 7 appeals, 3 tourist groups, 11 subscribers — each section guarded against
  re-seed duplication). `DatabaseSeeder` now chains reference → staff → demo. Verified on the dev
  MySQL; 189 tests still green.
- **2026-06-10** — Claude Design handoff implementation (`КЧС / КҲФ Design System`, derived from
  this codebase — tokens already matched, lifted verbatim). Implemented the genuinely-new pieces:
  (1) official locale-matched КЧС emblems made web-servable (`public/images/emblem-{tj,ru,en}.webp`,
  byte-identical to `resources/static/logo`) + `AppEmblem` component (locale-aware, falls back to tj);
  wired into the public header, admin sidebar, auth layout, and `AppLogo` — replacing the leftover
  default Laravel star/«Laravel Starter Kit» branding everywhere. (2) `HazardBadge` accessible
  component (colour + icon + text, never colour alone — fixes the a11y violation where the incidents
  pill forced white text on the yellow `elevated` level); public IncidentController now exposes
  `hazard_level` value; incidents archive uses it. 1 new test (incidents hazard payload); 189 total.
  types/build/lint/Pint clean. NB: live HTTP asset check deferred — Herd was unreachable at the time
  (transient); files are in the docroot so they serve once it's up.
- **2026-06-10** — Phase 13 multilingual completion: (1) all 11 public pages + alert banner
  converted to `t()` — ~90 dictionary keys ×3 locales in `lang/*/ui.php`, shared strings unified
  under `common.*`, zero hard-coded Cyrillic left in public React code; (2) `lang/*/enums.php` —
  all 12 enum `label()` now `__('enums.<group>.'.$this->value)` (public/CMS enum labels follow
  locale); (3) `lang/*/mail.php` — alert + subscription-confirmation templates/subjects localized,
  mailables sent with `->locale($subscriber->locale)`, `AlertNotification` takes the `HazardLevel`
  enum so the label resolves in the recipient locale; (4) `App\Support\LocaleUrls` + server-rendered
  canonical/hreflang/x-default in app.blade.php, `<html lang>` now valid BCP-47 (tj→tg);
  (5) CMS translation-status: `locales` row field + «Языки» tj/ru/en badge column in all 6 module
  indexes (field name unified). 9 new tests (SeoAlternates, enum locale, key-parity ×{ui,enums,mail});
  188 total, 955 assertions. types/build/lint/Pint clean. Orchestrated via 2 workflows (30 agents:
  12 string-inventory + merge/translate, 12 page conversions, 6 CMS badge modules).
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

**Local dev — social footer links DONE.** Next: map tile env/fallback or legacy 301 map (needs URLs).

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
