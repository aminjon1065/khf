<?php

namespace App\Services\Public;

use App\Models\Alert;
use App\Models\AlertTranslation;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemTranslation;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for Inertia shared props that hit the DB on every public navigation
 * (menus, active alert banner). Invalidated when source models change.
 */
class SharedPublicProps
{
    public const GROUP_MENUS = 'menus';

    public const GROUP_ALERTS = 'alerts';

    /**
     * @var array<string, list<class-string<Model>>>
     */
    private const INVALIDATORS = [
        self::GROUP_MENUS => [
            Menu::class,
            MenuItem::class,
            MenuItemTranslation::class,
        ],
        self::GROUP_ALERTS => [
            Alert::class,
            AlertTranslation::class,
        ],
    ];

    public function __construct(
        private MenuFormatter $menuFormatter,
    ) {}

    /**
     * Register model observers that bump shared-prop cache versions.
     */
    public function registerInvalidation(): void
    {
        foreach (self::INVALIDATORS as $group => $models) {
            foreach ($models as $model) {
                $model::saved(fn () => $this->bump($group));
                $model::deleted(fn () => $this->bump($group));
            }
        }
    }

    /**
     * Active primary/footer menus for the locale, keyed by location.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function menus(string $locale): array
    {
        return $this->remember(self::GROUP_MENUS, $locale, function () use ($locale): array {
            $menus = Menu::query()
                ->where('is_active', true)
                ->with(['items' => function ($query): void {
                    $query->orderBy('sort_order')->with('translations');
                }])
                ->get();

            $formatted = [];

            foreach ($menus as $menu) {
                $formatted[$menu->location] = $this->menuFormatter->formatTree(
                    $menu->items->where('parent_id', null),
                    $menu->items,
                    $locale,
                );
            }

            return $formatted;
        });
    }

    /**
     * Active emergency alerts for the locale banner, ordered by severity.
     *
     * @return list<array<string, mixed>>
     */
    public function activeAlerts(string $locale): array
    {
        return $this->remember(self::GROUP_ALERTS, $locale, function () use ($locale): array {
            $severity = ['critical' => 0, 'danger' => 1, 'elevated' => 2, 'normal' => 3];

            return Alert::active()
                ->with('translations')
                ->get()
                ->sortBy(fn (Alert $alert): int => $severity[$alert->hazard_level->value] ?? 9)
                ->values()
                ->map(function (Alert $alert) use ($locale): array {
                    $translation = $alert->translation($locale);

                    return [
                        'id' => $alert->id,
                        'level' => $alert->hazard_level->value,
                        'level_label' => $alert->hazard_level->label(),
                        'color' => $alert->hazard_level->color(),
                        'title' => $translation?->title,
                        'body' => $translation?->body,
                        'dismissible' => $alert->is_dismissible,
                    ];
                })
                ->all();
        });
    }

    public function version(string $group): int
    {
        return (int) Cache::get($this->versionKey($group), 1);
    }

    public function bump(string $group): void
    {
        if (! Cache::has($this->versionKey($group))) {
            Cache::forever($this->versionKey($group), 2);

            return;
        }

        Cache::increment($this->versionKey($group));
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function remember(string $group, string $locale, Closure $callback): mixed
    {
        if (! (bool) config('cms.content_cache.enabled', true)) {
            return $callback();
        }

        return Cache::remember(
            $this->key($group, $locale),
            (int) config('cms.content_cache.ttl', 3600),
            $callback,
        );
    }

    private function key(string $group, string $locale): string
    {
        return "shared.props.{$group}.v{$this->version($group)}.{$locale}";
    }

    private function versionKey(string $group): string
    {
        return "shared.props.version.{$group}";
    }
}
