<?php

namespace App\Services\Api\V1;

use App\Cms\ContentTypeDefinition;
use App\Cms\ContentTypeRegistry;
use App\Models\Page;
use App\Models\Post;
use App\Services\Cms\PublishedVersionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

/**
 * Resolves CMS collections for the headless read API (Statamic-style).
 */
class CollectionApiService
{
    /** @var array<string, string> */
    private const SHOW_ROUTES = [
        'page' => 'pages.show',
        'post' => 'news.show',
        'guide' => 'guides.show',
        'gallery' => 'gallery.show',
        'poll' => 'polls.show',
        'gov_service' => 'services.show',
        'vacancy' => 'vacancies.show',
        'tender' => 'tenders.show',
    ];

    public function __construct(
        private ContentTypeRegistry $contentTypes,
        private PublishedVersionService $publishedVersions,
    ) {}

    public function perPage(): int
    {
        return max(1, (int) config('cms.api.per_page', 15));
    }

    public function routePattern(): string
    {
        $handles = array_merge(
            $this->contentTypes->handles(),
            array_keys(config('cms.api.collection_aliases', [])),
        );

        return implode('|', array_unique($handles));
    }

    public function resolveType(string $collection): ContentTypeDefinition
    {
        $aliases = config('cms.api.collection_aliases', []);
        $handle = $aliases[$collection] ?? $collection;

        if (! $this->contentTypes->has($handle)) {
            throw new InvalidArgumentException("Unknown API collection [{$collection}].");
        }

        return $this->contentTypes->get($handle);
    }

    /**
     * @return Builder<Model>
     */
    public function query(ContentTypeDefinition $type): Builder
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;
        $query = $modelClass::query();

        $scope = config("cms.api.scopes.{$type->handle}", 'published');

        if ($scope === 'active' && method_exists($modelClass, 'scopeActive')) {
            $query->active();
        } elseif ($scope === 'published' && method_exists($modelClass, 'scopePublished')) {
            $query->published();
        }

        return $query->with($this->eagerLoads($type));
    }

    public function findBySlug(ContentTypeDefinition $type, string $slug): ?Model
    {
        if ($type->handle === 'page') {
            $pageId = $this->publishedVersions->resolvePublishedPageId($slug);

            if ($pageId === null) {
                return null;
            }

            return $this->prepareForApi(
                $type,
                Page::published()->with($this->eagerLoads($type))->whereKey($pageId)->first(),
            );
        }

        if ($type->handle === 'post') {
            $postId = $this->publishedVersions->resolvePublishedPostId($slug);

            if ($postId === null) {
                return null;
            }

            return $this->prepareForApi(
                $type,
                Post::published()->with($this->eagerLoads($type))->whereKey($postId)->first(),
            );
        }

        $record = $this->query($type)
            ->whereHas('translations', fn (Builder $inner) => $inner->where('slug', $slug))
            ->first();

        if ($record !== null) {
            return $this->prepareForApi($type, $record);
        }

        if (ctype_digit($slug)) {
            return $this->prepareForApi(
                $type,
                $this->query($type)->whereKey((int) $slug)->first(),
            );
        }

        return null;
    }

    public function prepareForApi(ContentTypeDefinition $type, ?Model $record): ?Model
    {
        if ($record === null) {
            return null;
        }

        if (in_array($type->handle, ['page', 'post'], true)) {
            return $this->publishedVersions->forPublicDisplay($record);
        }

        return $record;
    }

    public function publicUrl(ContentTypeDefinition $type, Model $record, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $routeName = self::SHOW_ROUTES[$type->handle] ?? null;

        if ($routeName === null || ! Route::has($routeName) || ! method_exists($record, 'translation')) {
            return null;
        }

        $slug = $record->translation($locale)?->slug;

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return route($routeName, ['locale' => $locale, 'slug' => $slug]);
    }

    /**
     * @return list<string>
     */
    private function eagerLoads(ContentTypeDefinition $type): array
    {
        return match ($type->handle) {
            'post' => ['translations', 'category.translations', 'tags.translations'],
            'page' => ['translations', 'media', 'tags.translations'],
            'incident', 'alert' => ['translations', 'region.translations'],
            default => ['translations'],
        };
    }
}
