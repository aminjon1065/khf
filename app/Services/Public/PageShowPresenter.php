<?php

namespace App\Services\Public;

use App\Models\Page;
use App\Services\Cms\PublishedVersionService;
use App\Support\LocaleUrls;
use App\Support\PreviewUrls;

/**
 * Builds Inertia props for the public static page view (and admin live preview).
 *
 * {@see SHOW_WITH} is the eager-load contract for callers; {@see present()} also loadMissing()s it.
 */
class PageShowPresenter
{
    /**
     * Relations required by {@see present()}.
     *
     * @var list<string>
     */
    public const SHOW_WITH = ['translations', 'media'];

    public function __construct(
        private LocaleUrls $localeUrls,
        private PreviewUrls $previewUrls,
        private PublishedVersionService $publishedVersions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Page $page, string $locale, bool $preview = false): array
    {
        $page->loadMissing(self::SHOW_WITH);

        $resolved = $page->translation($locale);

        abort_if($resolved === null, 404);

        $slugByLocale = $page->translations->pluck('slug', 'locale')->all();

        $urls = $preview
            ? $this->previewUrls->contentUrls('page', $page->id, $slugByLocale)
            : $this->localeUrls->contentUrls('pages.show', $slugByLocale);

        return [
            'page' => [
                'id' => $page->id,
                'title' => $resolved->title,
                'content' => $resolved->content,
                'blocks' => $resolved->blocks ?? [],
                'locale' => $resolved->locale,
                'updated_at' => $page->updated_at?->format('d.m.Y'),
            ],
            'seo' => [
                'title' => $resolved->seo_title ?: $resolved->title,
                'description' => $resolved->seo_description,
                'image' => $this->publishedVersions->publicCoverUrl($page) ?: null,
                'noindex' => $preview,
            ],
            'localeSwitch' => $urls['switch'],
            'seoAlternates' => $urls['alternates'],
            'isPreview' => $preview,
        ];
    }
}
