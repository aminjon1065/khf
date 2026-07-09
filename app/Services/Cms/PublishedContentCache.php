<?php

namespace App\Services\Cms;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Public\PageShowPresenter;
use App\Services\Public\PostShowPresenter;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Statamic Stache-like cache for published CMS entries: versioned keys per collection,
 * locale-scoped payloads, slug indexes, and deploy-time warming.
 */
class PublishedContentCache
{
    public const LOCALE_AGNOSTIC = '_';

    /** @var array<class-string<Model>, list<string>> */
    private const RELATED_COLLECTION_BUMPS = [
        Category::class => ['post'],
        Tag::class => ['post'],
    ];

    public function enabled(): bool
    {
        return (bool) config('cms.content_cache.enabled', true);
    }

    public function ttl(): int
    {
        return (int) config('cms.content_cache.ttl', 3600);
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function remember(string $type, string $locale, string $key, Closure $callback): mixed
    {
        if (! $this->enabled()) {
            return $callback();
        }

        return Cache::remember(
            $this->key($type, $locale, $key),
            $this->ttl(),
            $callback,
        );
    }

    public function key(string $type, string $locale, string $key): string
    {
        return "cms.content.{$type}.v{$this->version($type)}.{$locale}.{$key}";
    }

    public function version(string $type): int
    {
        return (int) Cache::get($this->versionKey($type), 1);
    }

    public function bump(string $type): void
    {
        if (! Cache::has($this->versionKey($type))) {
            Cache::forever($this->versionKey($type), 2);

            return;
        }

        Cache::increment($this->versionKey($type));
    }

    public function bumpForModel(Model $model): void
    {
        $type = $this->typeForModel($model);

        if ($type !== null) {
            $this->bump($type);
        }

        foreach (self::RELATED_COLLECTION_BUMPS[$model::class] ?? [] as $relatedType) {
            $this->bump($relatedType);
        }
    }

    /**
     * @return array<string, int>
     */
    public function slugIndex(string $type): array
    {
        return $this->remember($type, self::LOCALE_AGNOSTIC, 'slug-index', fn (): array => $this->buildSlugIndex($type));
    }

    public function resolveSlugId(string $type, string $slug): ?int
    {
        $id = $this->slugIndex($type)[$slug] ?? null;

        return is_int($id) ? $id : null;
    }

    /**
     * @return array{slug_indexes: int, locale_fragments: int, globals: int}
     */
    public function warm(?string $onlyLocale = null): array
    {
        $locales = $onlyLocale !== null
            ? [$onlyLocale]
            : (config('app.locales') ?? ['tj', 'ru', 'en']);

        $stats = [
            'slug_indexes' => 0,
            'locale_fragments' => 0,
            'globals' => 0,
        ];

        foreach (['page', 'post'] as $type) {
            $index = $this->slugIndex($type);
            $stats['slug_indexes'] += count($index);
        }

        $globalResolver = app(GlobalResolver::class);

        foreach ($globalResolver->definitions() as $handle => $definition) {
            unset($definition);

            foreach ($locales as $locale) {
                $globalResolver->resolve($handle, $locale);
                $stats['globals']++;
            }
        }

        foreach ($locales as $locale) {
            $this->warmLocaleFragments($locale);
            $stats['locale_fragments']++;
        }

        return $stats;
    }

    public function typeForModel(Model $model): ?string
    {
        foreach (config('cms.content_types', []) as $handle => $definition) {
            if (($definition['model'] ?? null) === $model::class) {
                return $handle;
            }
        }

        return null;
    }

    private function versionKey(string $type): string
    {
        return "cms.content.version.{$type}";
    }

    /**
     * @return array<string, int>
     */
    private function buildSlugIndex(string $type): array
    {
        return match ($type) {
            'page' => $this->buildPageSlugIndex(),
            'post' => $this->buildPostSlugIndex(),
            default => [],
        };
    }

    /**
     * @return array<string, int>
     */
    private function buildPageSlugIndex(): array
    {
        $index = [];

        Page::query()
            ->published()
            ->with('translations')
            ->cursor()
            ->each(function (Page $page) use (&$index): void {
                if ($page->hasPublishedSnapshot()) {
                    foreach ($page->published_snapshot['translations'] ?? [] as $translation) {
                        $slug = $translation['slug'] ?? null;

                        if (is_string($slug) && $slug !== '') {
                            $index[$slug] = $page->getKey();
                        }
                    }

                    return;
                }

                foreach ($page->translations as $translation) {
                    if ($translation->slug !== null && $translation->slug !== '') {
                        $index[$translation->slug] = $page->getKey();
                    }
                }
            });

        return $index;
    }

    /**
     * @return array<string, int>
     */
    private function buildPostSlugIndex(): array
    {
        $index = [];

        Post::query()
            ->published()
            ->with('translations')
            ->cursor()
            ->each(function (Post $post) use (&$index): void {
                if ($post->hasPublishedSnapshot()) {
                    foreach ($post->published_snapshot['translations'] ?? [] as $translation) {
                        $slug = $translation['slug'] ?? null;

                        if (is_string($slug) && $slug !== '') {
                            $index[$slug] = $post->getKey();
                        }
                    }

                    return;
                }

                foreach ($post->translations as $translation) {
                    if ($translation->slug !== null && $translation->slug !== '') {
                        $index[$translation->slug] = $post->getKey();
                    }
                }
            });

        return $index;
    }

    private function warmLocaleFragments(string $locale): void
    {
        $publishedVersions = app(PublishedVersionService::class);
        $postPresenter = app(PostShowPresenter::class);
        $pagePresenter = app(PageShowPresenter::class);

        $this->remember('post', $locale, 'categories', function () use ($locale): array {
            return Category::with(['translations'])
                ->get()
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->translation($locale)?->name,
                ])
                ->all();
        });

        $this->remember('post', $locale, 'home.latest', function () use ($locale, $publishedVersions, $postPresenter): array {
            return Post::published()
                ->with(['translations', 'category.translations', 'media'])
                ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
                ->orderByDesc('published_at')
                ->limit(6)
                ->get()
                ->map(fn (Post $post): array => $postPresenter->card(
                    $publishedVersions->forPublicDisplay($post),
                    $locale,
                ))
                ->all();
        });

        foreach ($this->slugIndex('page') as $slug => $pageId) {
            $this->remember('page', $locale, "show.{$slug}", function () use ($locale, $pageId, $publishedVersions, $pagePresenter): ?array {
                $page = Page::published()->with(['translations', 'media'])->whereKey($pageId)->first();

                if ($page === null) {
                    return null;
                }

                return $pagePresenter->present($publishedVersions->forPublicDisplay($page), $locale);
            });
        }

        foreach ($this->slugIndex('post') as $slug => $postId) {
            $this->remember('post', $locale, "show.{$slug}", function () use ($locale, $postId, $publishedVersions, $postPresenter): ?array {
                $post = Post::published()
                    ->whereKey($postId)
                    ->with(['category.translations', 'media', 'author', 'translations', 'tags.translations'])
                    ->first();

                if ($post === null) {
                    return null;
                }

                return $postPresenter->present($publishedVersions->forPublicDisplay($post), $locale);
            });
        }
    }
}
