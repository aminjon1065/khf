# KCHS / КҲФ Portal

Official multilingual web portal of the **Committee for Emergency Situations and Civil Defense**
(КЧС / КҲФ) of the Republic of Tajikistan. Replaces the legacy `kchs.tj` / `khf.tj` sites.

One Laravel + Inertia (React) codebase serves:

- **Public portal** — news, GIS incident map, alerts/subscriptions, documents, appeals, tourist groups, search
- **Staff CMS** — blueprint-driven content, media, moderation, RBAC, audit log

Mandatory locales: **Tajik (tj)**, **Russian (ru)**; **English (en)** for partners.

> Product requirements, decisions (D-1…), progress, and risks live in [`plan.md`](plan.md).  
> Production deploy notes: [`DEPLOY.md`](DEPLOY.md).

---

## Stack

| Layer | Versions |
|-------|----------|
| PHP / Laravel | 8.3+ / Laravel 13 |
| Frontend | Inertia 3, React 19, TypeScript, Tailwind v4, Vite |
| Auth | Fortify (2FA + passkeys) |
| Data | MySQL 8, Spatie permission / medialibrary / activitylog / responsecache |

**Production constraint:** shared hosting — DB cache/session/queue, cron `schedule:run`, SSR off, no Redis/Reverb.

---

## Local setup

```bash
composer setup
# or manually:
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

Dev (server + queue + logs + Vite):

```bash
composer run dev
```

Optional Sail: see `DEPLOY.md` §2.

---

## Useful commands

```bash
php artisan test --compact          # Pest suite
vendor/bin/pint --dirty             # PHP style
npm run types:check                 # TypeScript
npm run lint:check                  # ESLint
php artisan wayfinder:generate      # Typed routes (also via Vite plugin)
```

---

## Architecture notes

- CMS types: `config/cms.php` + YAML blueprints in `resources/blueprints/`
- Simple entry CRUD: `App\Services\Admin\ContentEntryService` (statistic, faq, gov_service, leader, gallery, subdivision, poll, guide, document, …)
- Admin mutating routes: `can:` middleware (see `AdminRoutePermissionsTest`)
- PII inboxes: Eloquent policies under `app/Policies/`
- Map tiles: `MAP_TILE_URL` / `config/map.php` (ТЗ §10.8)

---

## AI / agent guidelines

See [`AGENTS.md`](AGENTS.md) and [`CLAUDE.md`](CLAUDE.md) (Laravel Boost conventions).
