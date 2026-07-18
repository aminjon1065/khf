<?php

namespace App\Services\Public;

use App\Models\Post;
use App\Services\Cms\PublishedVersionService;
use App\Support\LocaleUrls;
use App\Support\PreviewUrls;

/**
 * Builds Inertia props for the public news/material detail view (and admin live preview).
 *
 * Eager-load contracts ({@see CARD_WITH}, {@see SHOW_WITH}) are the single source of truth for
 * callers; {@see card()} / {@see present()} also {@see Model::loadMissing()} as a safety net so
 * Inertia navigations cannot trip {@see Model::preventLazyLoading()}.
 */
class PostShowPresenter
{
    /**
     * Relations required by {@see card()}.
     *
     * @var list<string>
     */
    public const CARD_WITH = ['translations', 'category.translations', 'media'];

    /**
     * Relations required by {@see present()} (excluding related cards, which use {@see CARD_WITH}).
     *
     * @var list<string>
     */
    public const SHOW_WITH = ['category.translations', 'media', 'author', 'translations', 'tags.translations'];

    public function __construct(
        private LocaleUrls $localeUrls,
        private PreviewUrls $previewUrls,
        private PublishedVersionService $publishedVersions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Post $post, string $locale, bool $preview = false): array
    {
        $post->loadMissing(self::SHOW_WITH);

        $resolved = $post->translation($locale);

        abort_if($resolved === null, 404);

        $slugByLocale = $post->translations->pluck('slug', 'locale')->all();

        $urls = $preview
            ? $this->previewUrls->contentUrls('post', $post->id, $slugByLocale)
            : $this->localeUrls->contentUrls('news.show', $slugByLocale);

        return [
            'post' => [
                'id' => $post->id,
                'title' => $resolved->title,
                'excerpt' => $resolved->excerpt,
                'body' => $resolved->body,
                'locale' => $resolved->locale,
                'type_label' => $post->type->label(),
                'category' => $post->category?->translation($locale)?->name,
                'tags' => $post->tags
                    ->map(fn ($tag) => $tag->translation($locale)?->name)
                    ->filter()
                    ->values()
                    ->all(),
                'cover_url' => $this->publishedVersions->publicCoverUrl($post) ?: null,
                'gallery' => $post->getMedia(Post::GALLERY_COLLECTION)->map(fn ($media) => [
                    'name' => $media->name ?: $media->file_name,
                    'url' => $media->getUrl(),
                    'thumb' => $media->getUrl('thumb'),
                ])->all(),
                'attachments' => $post->getMedia(Post::ATTACHMENTS_COLLECTION)->map(fn ($media) => [
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->human_readable_size,
                    'ext' => pathinfo($media->file_name, PATHINFO_EXTENSION),
                ])->all(),
                'author' => $post->author?->name,
                'published_at' => $post->published_at?->format('d.m.Y'),
            ],
            'related' => $preview
                ? []
                : Post::published()
                    ->whereKeyNot($post->id)
                    ->where('category_id', $post->category_id)
                    ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
                    ->with(self::CARD_WITH)
                    ->orderByDesc('published_at')
                    ->limit(3)
                    ->get()
                    ->map(fn (Post $related) => $this->card($related, $locale))
                    ->all(),
            'localeSwitch' => $urls['switch'],
            'seoAlternates' => $urls['alternates'],
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'NewsArticle',
                'headline' => $resolved->title,
                'image' => $this->publishedVersions->publicCoverUrl($post) ?: url('/images/pwa-512.png'),
                'datePublished' => $post->published_at?->toIso8601String(),
                'dateModified' => $post->updated_at?->toIso8601String(),
                'author' => [
                    '@type' => 'Organization',
                    'name' => $post->author?->name ?? trans('ui.site.short_name'),
                ],
                'publisher' => [
                    '@type' => 'GovernmentOrganization',
                    'name' => trans('ui.site.full_name'),
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => url('/images/pwa-512.png'),
                    ],
                ],
                'description' => $resolved->excerpt,
            ],
            'isPreview' => $preview,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function card(Post $post, string $locale): array
    {
        $post->loadMissing(self::CARD_WITH);

        $translation = $post->translation($locale);

        return [
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'excerpt' => $translation?->excerpt,
            'category' => $post->category?->translation($locale)?->name,
            'cover_url' => $post->getFirstMediaUrl(Post::COVER_COLLECTION, 'thumb') ?: null,
            'published_at' => $post->published_at?->format('d.m.Y'),
        ];
    }
}
