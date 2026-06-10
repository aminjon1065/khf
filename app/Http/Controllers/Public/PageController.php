<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageTranslation;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * Render a published static content page by its current-locale slug (ТЗ §5, §6 — «О Комитете»,
     * «Деятельность», «Контакты» и пр.). Content is CMS-managed and was sanitised on save.
     */
    public function show(string $locale, string $slug): Response
    {
        $appLocale = app()->getLocale();

        $translation = PageTranslation::query()
            ->where('locale', $appLocale)
            ->where('slug', $slug)
            ->first();

        abort_if($translation === null, 404);

        $page = Page::published()->whereKey($translation->page_id)->first();

        abort_if($page === null, 404);

        return Inertia::render('public/pages/show', [
            'page' => [
                'title' => $translation->title,
                'content' => $translation->content,
                'updated_at' => $page->updated_at?->format('d.m.Y'),
            ],
            'seo' => [
                'title' => $translation->seo_title ?: $translation->title,
                'description' => $translation->seo_description,
            ],
        ]);
    }
}
