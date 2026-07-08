<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\GovService;
use App\Models\Guide;
use App\Models\Page;
use App\Models\Poll;
use App\Models\Post;
use App\Models\Statistic;
use App\Models\Tender;
use App\Models\Vacancy;
use App\Support\LocaleUrls;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class SitemapController extends Controller
{
    public function index(LocaleUrls $localeUrls): Response
    {
        // Version the cache key by the newest content timestamp so the sitemap refreshes as soon as
        // a post/guide/page is updated, rather than serving stale URLs until the hourly TTL lapses.
        $version = collect([
            Post::max('updated_at'),
            Guide::max('updated_at'),
            Page::max('updated_at'),
            Vacancy::max('updated_at'),
            Tender::max('updated_at'),
            Gallery::max('updated_at'),
            Faq::max('updated_at'),
            Statistic::max('updated_at'),
            Poll::max('updated_at'),
            GovService::max('updated_at'),
        ])->filter()->max() ?? 'empty';

        $xml = Cache::remember('sitemap.xml.'.$version, now()->addHour(), function () use ($localeUrls) {
            $locales = config('app.locales');

            $urls = [];

            // Helper to build url entry
            $addUrl = function ($baseRoute, $params = [], $lastmod = null) use ($locales, $localeUrls, &$urls) {
                $alternates = [];
                $mainUrl = '';

                foreach ($locales as $locale) {
                    $routeParams = array_merge(['locale' => $locale], $params);
                    // For models, we might need a specific slug per locale. Let's assume generic paths for now or pass localized params.
                    // Actually, generic paths work if $params doesn't depend on locale. For Post, slug is per locale!

                    if (isset($params['_model'])) {
                        $model = $params['_model'];
                        $translation = $model->translation($locale);
                        if (! $translation) {
                            continue;
                        }
                        $routeParams['slug'] = $translation->slug;
                        // remove model from params
                        unset($routeParams['_model']);
                    }

                    try {
                        $url = URL::route($baseRoute, $routeParams);
                        $alternates[] = [
                            'hreflang' => $localeUrls->hreflang($locale),
                            'href' => $url,
                        ];
                        if ($locale === app()->getLocale() || empty($mainUrl)) {
                            $mainUrl = $url;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if ($mainUrl) {
                    $urls[] = [
                        'loc' => $mainUrl,
                        'lastmod' => $lastmod ? $lastmod->toAtomString() : now()->toAtomString(),
                        'alternates' => $alternates,
                    ];
                }
            };

            // Static routes
            $staticRoutes = [
                'welcome', 'search.index', 'news.index', 'incidents.index',
                'map.index', 'documents.index', 'guides.index', 'contacts.index',
                'appeals.create', 'tourist-groups.create', 'subscriptions.create',
                'vacancies.index', 'tenders.index',
                'leadership.index', 'structure.index',
                'gallery.index', 'faq.index', 'polls.index', 'services.index', 'statistics.index',
            ];

            foreach ($staticRoutes as $route) {
                $addUrl($route);
            }

            // Posts
            Post::published()->with('translations')->each(function ($post) use ($addUrl) {
                $addUrl('news.show', ['_model' => $post], $post->updated_at);
            });

            // Guides
            Guide::published()->with('translations')->each(function ($guide) use ($addUrl) {
                $addUrl('guides.show', ['_model' => $guide], $guide->updated_at);
            });

            // Pages
            Page::published()->with('translations')->each(function ($page) use ($addUrl) {
                $addUrl('pages.show', ['_model' => $page], $page->updated_at);
            });

            // Vacancies (ТЗ §20 «н», §44 — civil-service postings in the hierarchical sitemap).
            Vacancy::published()->with('translations')->each(function ($vacancy) use ($addUrl) {
                $addUrl('vacancies.show', ['_model' => $vacancy], $vacancy->updated_at);
            });

            // Tenders (ТЗ §9, §44 — procurement notices in the hierarchical sitemap).
            Tender::published()->with('translations')->each(function ($tender) use ($addUrl) {
                $addUrl('tenders.show', ['_model' => $tender], $tender->updated_at);
            });

            // Photo galleries (ТЗ §20 «ш», §44).
            Gallery::published()->with('translations')->each(function ($gallery) use ($addUrl) {
                $addUrl('gallery.show', ['_model' => $gallery], $gallery->updated_at);
            });

            return view('sitemap', compact('urls'))->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }
}
