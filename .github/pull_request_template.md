## Summary

<!-- What and why (1–3 bullets). Link ТЗ / plan.md item if applicable. -->

## Type of change

- [ ] Bug fix
- [ ] Tech debt / refactor (A-track)
- [ ] New feature (C-track)
- [ ] Tests / CI only
- [ ] Docs / DX

## Review checklist

### Scope & conventions
- [ ] Matches namespace split (`Public/` · `Admin/` · `Api/V1/`)
- [ ] One concern per PR (no mix of CMS CRUD refactor + product feature)
- [ ] PHP formatted with Pint (`vendor/bin/pint --dirty`)

### Backend
- [ ] Mutating admin routes have `can:` (or documented allowlist / Form Request auth)
- [ ] Form Requests used — no inline `$request->validate()` in controllers
- [ ] Eager-load `translations` on list/hot paths when iterating
- [ ] HTML/blocks sanitized before persist
- [ ] Cache invalidation considered (response cache / published content / shared props)

### Frontend
- [ ] Page under correct `resources/js/pages/{public,admin,auth,settings}/` prefix
- [ ] Wayfinder (`@/routes` / `@/actions`) — no hardcoded URLs
- [ ] Permission-gated UI uses `usePermissions()`
- [ ] Strings in `lang/{tj,ru,en}/ui.php`
- [ ] `npm run types:check` and `npm run lint:check` pass (if JS touched)

### Security
- [ ] Throttling on public write endpoints
- [ ] PII checks server-side (not UI-only)
- [ ] Uploads use `SafeFileUpload` where applicable
- [ ] No secrets in the diff

### Tests & ops
- [ ] Pest feature test added/updated; `php artisan test --compact --filter=…` green
- [ ] Compatible with cron `queue:work --stop-when-empty` (no Redis/SSR unless approved)

## Test plan

<!-- Manual checks: role with permission / without / second locale -->

-
