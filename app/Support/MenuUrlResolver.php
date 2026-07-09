<?php

namespace App\Support;

use App\Cms\ContentTypeRegistry;
use App\Models\Page;
use Illuminate\Support\Facades\Route;

/**
 * Resolves CMS menu item targets to locale-aware public URLs (ТЗ §7.8).
 */
class MenuUrlResolver
{
    /**
     * Route names that must never appear in public navigation (auth / CMS only).
     *
     * @var list<string>
     */
    private const BLOCKED_ROUTE_NAMES = [
        'dashboard',
        'home',
        'login',
        'logout',
    ];

    public function resolve(?string $url, ?string $route, string $locale): ?string
    {
        $url = trim((string) $url);
        $route = trim((string) $route);

        if ($url !== '' && $this->isExternalUrl($url)) {
            return $url;
        }

        if ($route !== '') {
            if (in_array($route, self::BLOCKED_ROUTE_NAMES, true) || str_starts_with($route, 'admin.')) {
                return null;
            }

            if (str_starts_with($route, 'page.')) {
                return $this->resolvePageRoute((int) substr($route, 5), $locale);
            }

            if (str_starts_with($route, 'entry.')) {
                return $this->resolveCollectionEntryRoute($route, $locale);
            }

            if (Route::has($route)) {
                return route($route, $this->routeParameters($route, $locale));
            }
        }

        if ($url !== '') {
            return $this->resolveRelativePath($url, $locale);
        }

        return null;
    }

    private function isExternalUrl(string $url): bool
    {
        return str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://')
            || str_starts_with($url, 'mailto:')
            || str_starts_with($url, 'tel:');
    }

    private function resolvePageRoute(int $pageId, string $locale): ?string
    {
        if ($pageId <= 0) {
            return null;
        }

        $page = Page::query()->with('translations')->find($pageId);
        $slug = $page?->translation($locale)?->slug;

        if ($slug === null || $slug === '') {
            return null;
        }

        return route('pages.show', ['locale' => $locale, 'slug' => $slug]);
    }

    private function resolveCollectionEntryRoute(string $route, string $locale): ?string
    {
        $parts = explode('.', $route);

        if (count($parts) !== 3 || $parts[0] !== 'entry') {
            return null;
        }

        [, $handle, $id] = $parts;
        $entryId = (int) $id;

        if ($entryId <= 0) {
            return null;
        }

        $showRoute = config("cms.menu.entry_collections.{$handle}");

        if (! is_string($showRoute) || $showRoute === '' || ! Route::has($showRoute)) {
            return null;
        }

        $registry = app(ContentTypeRegistry::class);

        if (! $registry->has($handle)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $registry->get($handle)->modelClass;
        $entry = $modelClass::query()->with('translations')->whereKey($entryId)->first();

        if ($entry === null || ! method_exists($entry, 'translation')) {
            return null;
        }

        $slug = $entry->translation($locale)?->slug;

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return route($showRoute, ['locale' => $locale, 'slug' => $slug]);
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParameters(string $routeName, string $locale): array
    {
        $parameters = ['locale' => $locale];

        if (in_array($routeName, ['home'], true)) {
            unset($parameters['locale']);
        }

        return $parameters;
    }

    private function resolveRelativePath(string $url, string $locale): string
    {
        $path = str_starts_with($url, '/') ? $url : '/'.$url;

        if (preg_match('#^/(?:admin|dashboard|settings|login)(?:/|$)#', $path) === 1) {
            return $path;
        }

        $locales = config('app.locales', ['tj', 'ru', 'en']);
        $localePattern = implode('|', array_map('preg_quote', $locales));

        if (preg_match('/^\/(?:'.$localePattern.')(\/|$)/', $path) === 1) {
            return $path;
        }

        return '/'.$locale.$path;
    }
}
