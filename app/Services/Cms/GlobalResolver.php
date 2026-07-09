<?php

namespace App\Services\Cms;

use App\Cms\GlobalDefinition;
use App\Models\Language;
use App\Models\SiteGlobal;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves CMS global field values with config fallbacks.
 */
class GlobalResolver
{
    /**
     * @return array<string, GlobalDefinition>
     */
    public function definitions(): array
    {
        $definitions = [];

        foreach (config('cms.globals', []) as $handle => $config) {
            $definitions[$handle] = new GlobalDefinition(
                handle: $handle,
                label: $config['label'],
                blueprint: $config['blueprint'],
                fallback: $config['fallback'] ?? [],
                icon: $config['icon'] ?? 'settings',
            );
        }

        return $definitions;
    }

    public function definition(string $handle): ?GlobalDefinition
    {
        return $this->definitions()[$handle] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(string $handle, ?string $locale = null): array
    {
        $definition = $this->definition($handle);

        if ($definition === null) {
            return [];
        }

        $locale ??= app()->getLocale();

        return Cache::remember(
            $this->cacheKey($handle, $locale),
            now()->addHour(),
            fn (): array => $this->resolveFresh($handle, $locale, $definition),
        );
    }

    public function forget(string $handle): void
    {
        foreach (Language::codes() ?: config('app.locales') as $locale) {
            Cache::forget($this->cacheKey($handle, $locale));
        }
    }

    /**
     * @return list<array{platform: string, url: string}>
     */
    public function socialLinks(): array
    {
        $data = $this->resolve('social');

        return collect([
            'telegram' => $data['telegram'] ?? null,
            'facebook' => $data['facebook'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'youtube' => $data['youtube'] ?? null,
            'x' => $data['x'] ?? null,
        ])
            ->filter(fn (mixed $url): bool => is_string($url) && $url !== '' && filter_var($url, FILTER_VALIDATE_URL) !== false)
            ->map(fn (string $url, string $platform): array => [
                'platform' => $platform,
                'url' => $url,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{url: string, photo: string}
     */
    public function president(): array
    {
        $data = $this->resolve('president');

        return [
            'url' => (string) ($data['url'] ?? config('president.url')),
            'photo' => (string) ($data['photo'] ?? config('president.photo')),
        ];
    }

    /**
     * Footer links and optional copyright notice (useful resources column + hotline).
     *
     * @return array{government_url: string|null, egov_url: string|null, hotline: string, copyright: string|null, resource_links: list<array{label: string, url: string}>}
     */
    public function footer(): array
    {
        $data = $this->resolve('footer');

        return [
            'government_url' => $this->nullableUrl($data['government_url'] ?? null),
            'egov_url' => $this->nullableUrl($data['egov_url'] ?? null),
            'hotline' => $this->digitsOnly((string) ($data['hotline'] ?? '112')) ?: '112',
            'copyright' => $this->nullableString($data['copyright'] ?? null),
            'resource_links' => $this->normalizeResourceLinks($data['resource_links'] ?? []),
        ];
    }

    /**
     * Default SEO meta when a page does not provide its own values.
     *
     * @return array{title: string, description: string, image: string}
     */
    public function seoDefaults(): array
    {
        $data = $this->resolve('seo_defaults');
        $locale = app()->getLocale();
        $emblemLocale = in_array($locale, ['tj', 'ru', 'en'], true) ? $locale : 'tj';

        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $image = $data['image'] ?? null;

        return [
            'title' => is_string($title) && $title !== '' ? $title : (string) config('app.name', 'КЧС'),
            'description' => is_string($description) && $description !== '' ? $description : trans('ui.site.full_name'),
            'image' => $this->absoluteAssetUrl(
                is_string($image) && $image !== '' ? $image : "/images/emblem-{$emblemLocale}.webp",
            ),
        ];
    }

    private function normalizeResourceLinks(mixed $links): array
    {
        if (! is_array($links)) {
            return [];
        }

        return collect($links)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->map(function (array $row): ?array {
                $label = trim((string) ($row['label'] ?? ''));
                $url = $this->nullableUrl($row['url'] ?? null);

                if ($label === '' || $url === null) {
                    return null;
                }

                return [
                    'label' => $label,
                    'url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function nullableUrl(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function absoluteAssetUrl(string $path): string
    {
        if ($path === '') {
            return url('/images/emblem-tj.webp');
        }

        if (filter_var($path, FILTER_VALIDATE_URL) !== false) {
            return $path;
        }

        return url($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFresh(string $handle, string $locale, GlobalDefinition $definition): array
    {
        $global = SiteGlobal::query()->where('handle', $handle)->first();

        if ($global === null) {
            return $definition->fallback;
        }

        $stored = $global->fieldData($locale);

        if ($stored === []) {
            $stored = $global->fieldData(config('app.fallback_locale'));
        }

        return array_merge($definition->fallback, $stored);
    }

    private function cacheKey(string $handle, string $locale): string
    {
        return "cms.global.{$handle}.{$locale}";
    }
}
