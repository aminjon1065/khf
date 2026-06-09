<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Public homepage (ТЗ §6.1): latest news + quick access. Alert banner, operational situation
     * and map widget are wired as those modules land.
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $latestPosts = Post::published()
            ->with(['translations', 'category.translations', 'media'])
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
            ->orderByDesc('published_at')
            ->limit(6)
            ->get()
            ->map(function (Post $post) use ($locale): array {
                $translation = $post->translation($locale);

                return [
                    'title' => $translation?->title,
                    'slug' => $translation?->slug,
                    'excerpt' => $translation?->excerpt,
                    'category' => $post->category?->translation($locale)?->name,
                    'cover_url' => $post->getFirstMediaUrl(Post::COVER_COLLECTION, 'thumb') ?: null,
                    'published_at' => $post->published_at?->format('d.m.Y'),
                ];
            })
            ->all();

        return Inertia::render('public/home', [
            'latestPosts' => $latestPosts,
        ]);
    }
}
