# CMS Roadmap — путь к Statamic-level CMS

> План развития административной части портала КЧС. Цель: мощная, расширяемая CMS поверх существующей Laravel/Inertia архитектуры — без миграции на flat-file Statamic.

**Статус:** фазы 1–8 и post-roadmap улучшения закрыты (март 2026).

---

## Текущее состояние

### Сильные стороны

| Область | Реализация |
|---------|------------|
| UI редактора | `CpPublishForm`, `CpPanel`, locale tabs, publish sidebar, autosave |
| Контент-модели | Pages, Posts, Documents, Guides, Gallery, FAQ, Polls, Services, Statistics, Leadership, Structure |
| Collections | `/admin/content` — единый Entry Browser |
| Blueprints | YAML в `resources/blueprints/`, `CpBlueprintForm`, `BlueprintValidator` |
| Workflow | `ContentStatus`: draft → moderation → published → archived |
| Working copy | `published_snapshot` для pages и posts |
| Расписание | `published_at` / `unpublished_at`, `ScopesPublicationWindow` |
| Ревизии | `HasRevisions` + diff UI в `RevisionsSlideOver` |
| Блоки | 8 типов, block sets YAML, DnD, registry renderer |
| Globals | CRUD в админке, headless API |
| Taxonomies | `TaxonomyService`, API, теги на pages + posts |
| Медиа | Папки, focal point, alt per locale, tags, usage, bulk ops |
| Меню | Дерево страниц, collection entries, mega-menu preview |
| Права | 40+ permissions, Spatie Permission, 2FA, 4 editorial роли |
| Кэш | `PublishedContentCache`, response cache, `cms:cache-warm` |
| Redirects | `/admin/redirects` |
| Import/Export | JSON/CSV в браузере коллекций |
| Headless API | `/api/v1/{collection}`, taxonomies, globals |
| UX | Command palette (⌘K) + поиск материалов, shortcuts, dashboard v2, contextual help |

### Оставшиеся улучшения (не блокеры)

1. ~~Метрика дублирования Admin controllers < 30%~~ — shared traits на 8 translatable CRUD-контроллерах
2. ~~Field types: `grid`, `replicator`~~ — реализованы (globals footer)
3. ~~Blueprint Browser (read-only)~~ — `/admin/blueprints`
4. ~~YAML Blueprint Editor~~ — `/admin/blueprints/{collection}/{name}/edit`
5. ~~Drag-and-drop field builder~~ — вкладка «Конструктор» в `/admin/blueprints/.../edit`
6. ~~`HasRevisions` на incidents/alerts~~ — история версий в формах ЧС

---

## Целевая архитектура

```
Control Panel (Inertia)
  ├── Entry Browser
  ├── Blueprint Editor (YAML → UI позже)
  ├── Globals
  ├── Assets Manager
  ├── Navigation Builder
  └── Live Preview

CMS Core (PHP)
  ├── Content Registry
  ├── Blueprint Engine
  ├── Field Types
  ├── Revision Service
  ├── Publish Pipeline
  └── Content Cache (Stache-like)

Storage: DB + Media Disk + Blueprint YAML/JSON
Public: Inertia Pages + Block Renderer + Headless API
```

---

## Фаза 1 — Унификация ядра ✅

**Критерии готовности:**
- [x] `ContentTypeRegistry` зарегистрирован и покрыт тестами
- [x] `PageController` и `PostController` используют shared traits
- [x] Unified Entry Browser `/admin/content/{type}`
- [x] `CpBlueprintForm` для posts и pages
- [x] Дублирование в контроллерах < 30% — `ListsTranslatableContent`, `ManagesSoftDeletableContent`, `SavesContentRevisions` на pages/posts/documents/guides/vacancies/tenders/incidents/alerts

---

## Фаза 2 — Blueprint Engine ✅

**Критерии готовности:**
- [x] Blueprints для post и page
- [x] `CpBlueprintForm` рендерит основные field types
- [x] Валидация синхронизирована backend/frontend

---

## Фаза 3 — Блоки и контент ✅

**Критерии готовности:**
- [x] Block sets из YAML (`page`, `homepage`, `about`, `landing`)
- [x] DnD в blocks-field
- [x] Bard editor (TipTap) для rich text
- [x] Block Renderer v2 (registry + компонент на блок)

---

## Фаза 4 — Globals, Navigation, Taxonomies ✅

**Критерии готовности:**
- [x] Globals CRUD в админке
- [x] `config/president.php` перенесён в CMS
- [x] Taxonomy API для posts + pages
- [x] Дерево страниц и записи коллекций в меню
- [x] Mega-menu preview в админке

---

## Фаза 5 — Publishing Pipeline ✅

**Критерии готовности:**
- [x] Working copy / `published_snapshot` для pages и posts
- [x] Live preview для pages и posts
- [x] 4 editorial роли + очередь модерации
- [x] Revision diff UI

---

## Фаза 6 — Assets Manager ✅

**Критерии готовности:**
- [x] Folder tree в медиатеке
- [x] Focal point на изображениях
- [x] Alt per locale
- [x] Tags на файлах
- [x] Usage tracking
- [x] Bulk operations

---

## Фаза 7 — Performance & DX ✅

**Критерии готовности:**
- [x] Content cache с warm on deploy
- [x] Redirect manager в админке
- [x] Headless API
- [x] Import/Export JSON и CSV

---

## Фаза 8 — UX Polish ✅

| Фича | Статус |
|------|--------|
| Autosave | [x] |
| Keyboard shortcuts (⌘S, ⌘P) | [x] |
| Command palette (⌘K) | [x] |
| Поиск материалов в command palette | [x] |
| Dashboard v2 | [x] |
| Contextual help (`content-help-topics`) | [x] |

---

## Что НЕ переносим из Statamic

| Statamic | Причина |
|----------|---------|
| Flat-file storage | Eloquent + translations работают |
| Antlers | Inertia/React — правильный выбор |
| Multi-site | Достаточно locale prefix |
| Git integration | Overkill для gov portal |

---

## Ссылки на код

- CP UI: `resources/js/components/admin/cp/`
- Block registry: `resources/js/components/Public/blocks/`
- Blueprints: `resources/blueprints/`
- Block sets: `resources/blocksets/`
- CMS Core: `app/Cms/`, `config/cms.php`
- Admin routes: `routes/admin.php`
- Headless API: `routes/api.php`
