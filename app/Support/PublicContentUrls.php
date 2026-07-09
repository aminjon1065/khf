<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Support\Facades\Route;

/**
 * Builds public URLs for CMS content shown in the admin "view on site" actions.
 */
class PublicContentUrls
{
    /**
     * @return array<string, string>
     */
    public static function forPage(Page $page): array
    {
        $urls = [];

        foreach (config('app.locales', ['tj', 'ru', 'en']) as $locale) {
            $slug = $page->translation($locale)?->slug;

            if ($slug) {
                $urls[$locale] = route('pages.show', ['locale' => $locale, 'slug' => $slug]);
            }
        }

        return $urls;
    }

    /**
     * @return array<string, string>
     */
    public static function forPost(Post $post): array
    {
        $urls = [];

        foreach (config('app.locales', ['tj', 'ru', 'en']) as $locale) {
            $slug = $post->translation($locale)?->slug;

            if ($slug) {
                $urls[$locale] = route('news.show', ['locale' => $locale, 'slug' => $slug]);
            }
        }

        return $urls;
    }

    public static function isPublishedPublicRoute(string $routeName, string $locale): bool
    {
        if (! Route::has($routeName)) {
            return false;
        }

        return true;
    }
}
