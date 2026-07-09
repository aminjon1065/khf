<?php

namespace App\Services\Cms;

use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Captures and applies the last published snapshot for pages and posts.
 */
class PublishedVersionService
{
    /**
     * @param  Page|Post  $model
     */
    public function capture(Model $model): void
    {
        $model->loadMissing($this->relationsToLoad($model));

        $snapshot = $this->buildSnapshot($model);

        $model->forceFill([
            'published_snapshot' => $snapshot,
            'published_snapshot_at' => now(),
        ])->saveQuietly();
    }

    /**
     * @param  Page|Post  $model
     */
    public function hasUnpublishedChanges(Model $model): bool
    {
        if (! $model->hasPublishedSnapshot()) {
            return false;
        }

        $model->loadMissing($this->relationsToLoad($model));
        $currentFingerprint = $this->fingerprint($this->buildSnapshot($model, withFingerprint: false));
        $storedFingerprint = $model->published_snapshot['fingerprint'] ?? null;

        return $storedFingerprint !== $currentFingerprint;
    }

    /**
     * Overlay the published snapshot onto the model for public rendering.
     *
     * @param  Page|Post  $model
     */
    public function forPublicDisplay(Model $model): Model
    {
        if (! $model->hasPublishedSnapshot()) {
            return $model;
        }

        $snapshot = $model->published_snapshot;
        $attributes = $snapshot['attributes'] ?? [];

        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at']);

        $model->forceFill($attributes);

        $translationClass = match ($model::class) {
            Page::class => PageTranslation::class,
            Post::class => PostTranslation::class,
            default => throw new \InvalidArgumentException('Unsupported model for published version.'),
        };
        $foreignKey = match ($model::class) {
            Page::class => 'page_id',
            Post::class => 'post_id',
            default => throw new \InvalidArgumentException('Unsupported model for published version.'),
        };

        $translations = collect($snapshot['translations'] ?? [])
            ->map(function (array $translationAttributes) use ($translationClass, $foreignKey, $model): Model {
                $translation = new $translationClass;
                $translation->forceFill([
                    ...$translationAttributes,
                    $foreignKey => $model->getKey(),
                ]);
                $translation->exists = true;

                return $translation;
            });

        $model->setRelation('translations', $translations);

        if (isset($snapshot['tag_ids']) && method_exists($model, 'tags')) {
            $tags = Tag::query()
                ->whereIn('id', $snapshot['tag_ids'])
                ->with('translations')
                ->get();

            $model->setRelation('tags', $tags);
        }

        if (isset($snapshot['cover_url'])) {
            $model->setAttribute('published_cover_url', $snapshot['cover_url']);
        }

        return $model;
    }

    public function publicCoverUrl(Page|Post $model): ?string
    {
        $fromSnapshot = $model->getAttribute('published_cover_url');

        if (is_string($fromSnapshot) && $fromSnapshot !== '') {
            return $fromSnapshot;
        }

        return $model->getFirstMediaUrl($model::COVER_COLLECTION) ?: null;
    }

    public function resolvePublishedPageId(string $slug): ?int
    {
        $fromIndex = app(PublishedContentCache::class)->resolveSlugId('page', $slug);

        if ($fromIndex !== null) {
            return $fromIndex;
        }

        foreach (Page::published()->whereNotNull('published_snapshot')->cursor() as $page) {
            if ($this->slugExistsInSnapshot($page->published_snapshot, $slug)) {
                return $page->getKey();
            }
        }

        $translation = PageTranslation::query()->where('slug', $slug)->first();

        if ($translation === null) {
            return null;
        }

        $page = Page::published()->whereKey($translation->page_id)->first();

        if ($page === null || $page->hasPublishedSnapshot()) {
            return null;
        }

        return $page->getKey();
    }

    public function resolvePublishedPostId(string $slug): ?int
    {
        $fromIndex = app(PublishedContentCache::class)->resolveSlugId('post', $slug);

        if ($fromIndex !== null) {
            return $fromIndex;
        }

        foreach (Post::published()->whereNotNull('published_snapshot')->cursor() as $post) {
            if ($this->slugExistsInSnapshot($post->published_snapshot, $slug)) {
                return $post->getKey();
            }
        }

        $translation = PostTranslation::query()->where('slug', $slug)->first();

        if ($translation === null) {
            return null;
        }

        $post = Post::published()->whereKey($translation->post_id)->first();

        if ($post === null || $post->hasPublishedSnapshot()) {
            return null;
        }

        return $post->getKey();
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     */
    private function slugExistsInSnapshot(?array $snapshot, string $slug): bool
    {
        if ($snapshot === null) {
            return false;
        }

        foreach ($snapshot['translations'] ?? [] as $translation) {
            if (($translation['slug'] ?? null) === $slug) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Page|Post  $model
     * @return list<string>
     */
    private function relationsToLoad(Model $model): array
    {
        return ['translations', 'media', 'tags'];
    }

    /**
     * @param  Page|Post  $model
     * @return array<string, mixed>
     */
    private function buildSnapshot(Model $model, bool $withFingerprint = true): array
    {
        $attributes = match (true) {
            $model instanceof Page => collect($model->getAttributes())->only([
                'parent_id',
                'sort_order',
                'is_home',
            ])->all(),
            $model instanceof Post => collect($model->getAttributes())->only([
                'type',
                'category_id',
                'published_at',
                'unpublished_at',
            ])->all(),
            default => [],
        };

        $snapshot = [
            'attributes' => $attributes,
            'translations' => $model->translations
                ->map(fn (Model $translation): array => collect($translation->getAttributes())
                    ->except(['id', 'created_at', 'updated_at'])
                    ->all())
                ->values()
                ->all(),
            'cover_url' => $model->getFirstMediaUrl($model::COVER_COLLECTION) ?: null,
        ];

        if (method_exists($model, 'tags')) {
            $snapshot['tag_ids'] = $model->tags->pluck('id')->sort()->values()->all();
        }

        if ($withFingerprint) {
            $snapshot['fingerprint'] = $this->fingerprint($snapshot);
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function fingerprint(array $snapshot): string
    {
        return hash('xxh128', json_encode($this->normalizeForFingerprint($snapshot), JSON_THROW_ON_ERROR));
    }

    private function normalizeForFingerprint(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeForFingerprint($item), $value);
        }

        ksort($value);

        return array_map(fn (mixed $item): mixed => $this->normalizeForFingerprint($item), $value);
    }
}
