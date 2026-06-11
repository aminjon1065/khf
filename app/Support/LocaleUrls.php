<?php

namespace App\Support;

use App\Models\Language;
use Illuminate\Http\Request;

/**
 * Locale-aware URL helpers shared by the Inertia language switcher and the server-rendered
 * SEO tags (canonical / hreflang alternates, ТЗ §14, §15.1). Internal locale codes (`tj`)
 * are mapped to valid BCP-47 tags (`tg`) via the languages table `hreflang` column with a
 * static fallback for early-boot / DB-less contexts.
 */
class LocaleUrls
{
    /** @var array<string, string> */
    private const FALLBACK_HREFLANG = ['tj' => 'tg'];

    /**
     * Map of locale code → URL for the current request. On a localized public route the locale
     * segment is swapped while preserving the rest of the path and query; elsewhere it points
     * to that locale's homepage.
     *
     * @return array<string, string>
     */
    public function switchMap(Request $request): array
    {
        $codes = $this->supportedCodes();

        $segments = explode('/', trim($request->path(), '/'));
        $hasLocalePrefix = in_array($segments[0] ?? '', $codes, true);

        $queryString = $request->getQueryString();
        $query = $queryString !== null ? '?'.$queryString : '';

        $map = [];

        foreach ($codes as $code) {
            if ($hasLocalePrefix) {
                $segments[0] = $code;
                $path = implode('/', $segments);
            } else {
                $path = $code;
            }

            $map[$code] = url($path).$query;
        }

        return $map;
    }

    /**
     * Hreflang alternates for the current request, or an empty list outside localized public
     * routes (admin, auth) where alternate language versions do not exist.
     *
     * @return list<array{code: string, hreflang: string, url: string}>
     */
    public function alternates(Request $request): array
    {
        $codes = $this->supportedCodes();
        $segments = explode('/', trim($request->path(), '/'));

        if (! in_array($segments[0] ?? '', $codes, true)) {
            return [];
        }

        $alternates = [];

        foreach ($this->switchMap($request) as $code => $url) {
            $alternates[] = ['code' => $code, 'hreflang' => $this->hreflang($code), 'url' => $url];
        }

        return $alternates;
    }

    /**
     * Per-locale switch map + hreflang alternates for a slug-based detail page (guides.show,
     * pages.show, news.show). Slugs differ per locale, so the generic path-swap in switchMap()
     * would 404; here each locale uses its OWN translation slug, and locales with no translation
     * fall back to that locale's homepage. Returned to the page as `localeSwitch` + `seoAlternates`.
     *
     * @param  array<string, string>  $slugByLocale  locale code → that locale's slug
     * @return array{switch: array<string, string>, alternates: list<array{code: string, hreflang: string, url: string}>}
     */
    public function contentUrls(string $routeName, array $slugByLocale): array
    {
        $switch = [];
        $alternates = [];

        foreach ($this->supportedCodes() as $code) {
            $url = isset($slugByLocale[$code])
                ? route($routeName, ['locale' => $code, 'slug' => $slugByLocale[$code]])
                : url($code);

            $switch[$code] = $url;
            $alternates[] = ['code' => $code, 'hreflang' => $this->hreflang($code), 'url' => $url];
        }

        return ['switch' => $switch, 'alternates' => $alternates];
    }

    /**
     * Valid BCP-47 tag for an internal locale code (tj → tg).
     */
    public function hreflang(string $code): string
    {
        try {
            $hreflang = Language::query()->where('code', $code)->value('hreflang');

            if (is_string($hreflang) && $hreflang !== '') {
                return $hreflang;
            }
        } catch (\Throwable) {
            // Fall back to the static map below.
        }

        return self::FALLBACK_HREFLANG[$code] ?? $code;
    }

    /**
     * Default portal locale code (x-default target).
     */
    public function defaultCode(): string
    {
        try {
            $code = Language::defaultCode();

            if ($code !== '') {
                return $code;
            }
        } catch (\Throwable) {
            // Fall back to the config below.
        }

        return (string) config('app.fallback_locale');
    }

    /**
     * @return list<string>
     */
    public function supportedCodes(): array
    {
        try {
            $codes = Language::codes();

            if ($codes !== []) {
                return $codes;
            }
        } catch (\Throwable) {
            // Fall back to the static config allow-list below.
        }

        return config('app.locales');
    }
}
