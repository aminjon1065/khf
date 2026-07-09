<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Cms\PublishedContentCache;
use App\Services\Cms\PublishedVersionService;
use App\Services\Public\PageShowPresenter;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        private PageShowPresenter $presenter,
        private PublishedVersionService $publishedVersions,
        private PublishedContentCache $contentCache,
    ) {}

    /**
     * Render a published static content page by its current-locale slug (ТЗ §5, §6 — «О Комитете»,
     * «Деятельность», «Контакты» и пр.). Content is CMS-managed and was sanitised on save.
     */
    public function show(string $locale, string $slug): Response
    {
        $appLocale = app()->getLocale();

        $data = $this->contentCache->remember('page', $appLocale, "show.{$slug}", function () use ($appLocale, $slug) {
            $pageId = $this->publishedVersions->resolvePublishedPageId($slug);

            if ($pageId === null) {
                return null;
            }

            $page = Page::published()->with(['translations', 'media'])->whereKey($pageId)->first();

            if ($page === null) {
                return null;
            }

            $page = $this->publishedVersions->forPublicDisplay($page);

            return $this->presenter->present($page, $appLocale);
        });

        abort_if($data === null, 404);

        return Inertia::render('public/pages/show', $data);
    }
}
