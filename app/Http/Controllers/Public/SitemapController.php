<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Guide;
use App\Models\Page;
use App\Models\Post;
use App\Support\LocaleUrls;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class SitemapController extends Controller
{
    public function index(LocaleUrls $localeUrls): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () use ($localeUrls) {
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
                        if (!$translation) continue;
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
                'appeals.create', 'tourist-groups.create', 'subscriptions.create'
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

            return view('sitemap', compact('urls'))->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }
}
