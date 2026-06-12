<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Support\LocaleUrls;
use Illuminate\Support\Facades\Cache;
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

        $pageVersion = Page::max('updated_at') ?? 'empty';
        $cacheKey = 'page.'.$appLocale.'.'.$slug.'.'.$pageVersion;

        $data = Cache::remember($cacheKey, 3600, function () use ($appLocale, $slug) {
            $translation = PageTranslation::query()
                ->where('locale', $appLocale)
                ->where('slug', $slug)
                ->first();

            if ($translation === null) {
                return null;
            }

            $page = Page::published()->with('translations')->whereKey($translation->page_id)->first();

            if ($page === null) {
                return null;
            }

            $urls = app(LocaleUrls::class)->contentUrls(
                'pages.show',
                $page->translations->pluck('slug', 'locale')->all(),
            );

            return [
                'page' => [
                    'id' => $page->id,
                    'title' => $translation->title,
                    'content' => $translation->content,
                    'updated_at' => $page->updated_at?->format('d.m.Y'),
                ],
                'seo' => [
                    'title' => $translation->seo_title ?: $translation->title,
                    'description' => $translation->seo_description,
                ],
                'localeSwitch' => $urls['switch'],
                'seoAlternates' => $urls['alternates'],
            ];
        });

        abort_if($data === null, 404);

        return Inertia::render('public/pages/show', $data);
    }
}
