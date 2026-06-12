<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Support\LocaleUrls;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Public news / materials listing for the current locale (ТЗ §6.2).
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $page = request('page', 1);
        $cacheKey = 'posts.index.'.$locale.'.page.'.$page.'.'.(Post::max('updated_at') ?? 'empty');

        $posts = Cache::remember($cacheKey, 3600, function () use ($locale) {
            return Post::published()
                ->with(['translations', 'category.translations', 'media'])
                ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
                ->orderByDesc('published_at')
                ->paginate(12)
                ->through(fn (Post $post) => $this->card($post, $locale));
        });

        return Inertia::render('public/news/index', [
            'posts' => $posts,
        ]);
    }

    /**
     * A single published material resolved by its per-locale slug (ТЗ §6.2).
     */
    public function show(string $locale, string $slug): Response
    {
        $appLocale = app()->getLocale();

        $postVersion = Post::max('updated_at') ?? 'empty';
        $cacheKey = 'posts.show.'.$appLocale.'.'.$slug.'.'.$postVersion;

        $data = Cache::remember($cacheKey, 3600, function () use ($appLocale, $slug) {
            $translation = PostTranslation::query()
                ->where('locale', $appLocale)
                ->where('slug', $slug)
                ->first();

            if ($translation === null) {
                return null;
            }

            $post = Post::published()
                ->whereKey($translation->post_id)
                ->with(['category.translations', 'media', 'author', 'translations'])
                ->first();

            if ($post === null) {
                return null;
            }

            $urls = app(LocaleUrls::class)->contentUrls(
                'news.show',
                $post->translations->pluck('slug', 'locale')->all(),
            );

            return [
                'post' => [
                    'id' => $post->id,
                    'title' => $translation->title,
                    'excerpt' => $translation->excerpt,
                    'body' => $translation->body,
                    'type_label' => $post->type->label(),
                    'category' => $post->category?->translation($appLocale)?->name,
                    'cover_url' => $post->getFirstMediaUrl(Post::COVER_COLLECTION) ?: null,
                    'author' => $post->author?->name,
                    'published_at' => $post->published_at?->format('d.m.Y'),
                ],
                'recent' => Post::published()
                    ->whereKeyNot($post->id)
                    ->whereHas('translations', fn ($query) => $query->where('locale', $appLocale))
                    ->with(['translations'])
                    ->orderByDesc('published_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (Post $recent) => $this->card($recent, $appLocale))
                    ->all(),
                'localeSwitch' => $urls['switch'],
                'seoAlternates' => $urls['alternates'],
                'schema' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'NewsArticle',
                    'headline' => $translation->title,
                    'image' => $post->getFirstMediaUrl(Post::COVER_COLLECTION) ?: url('/images/emblem-tj.webp'),
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
                            'url' => url('/images/emblem-tj.webp'),
                        ],
                    ],
                    'description' => $translation->excerpt,
                ],
            ];
        });

        abort_if($data === null, 404);

        return Inertia::render('public/news/show', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function card(Post $post, string $locale): array
    {
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
