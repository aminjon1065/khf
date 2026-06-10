<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    /**
     * RSS 2.0 feed of the latest published news for the active locale (ТЗ §6.2, §15.3).
     */
    public function news(string $locale): Response
    {
        $appLocale = app()->getLocale();

        $posts = Post::published()
            ->whereHas('translations', fn ($query) => $query->where('locale', $appLocale))
            ->with('translations')
            ->orderByDesc('published_at')
            ->limit(30)
            ->get()
            ->map(function (Post $post) use ($appLocale): array {
                $translation = $post->translation($appLocale);

                return [
                    'title' => $translation?->title ?? '',
                    'description' => $translation?->excerpt ?? '',
                    'link' => route('news.show', ['locale' => $appLocale, 'slug' => $translation?->slug]),
                    'published_at' => $post->published_at,
                ];
            })
            ->all();

        return response()
            ->view('feeds.news', [
                'locale' => $appLocale,
                'title' => __('ui.news.heading').' — '.__('ui.site.short_name'),
                'description' => __('ui.site.full_name'),
                'self' => route('news.rss', ['locale' => $appLocale]),
                'home' => route('welcome', ['locale' => $appLocale]),
                'items' => $posts,
            ])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
