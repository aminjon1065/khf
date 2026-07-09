<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Services\Cms\PublishedContentCache;
use App\Services\Cms\PublishedVersionService;
use App\Services\Public\PostShowPresenter;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(
        private PostShowPresenter $presenter,
        private PublishedVersionService $publishedVersions,
        private PublishedContentCache $contentCache,
    ) {}

    /**
     * Public news / materials listing for the current locale (ТЗ §6.2).
     */
    public function index(): Response
    {
        $locale = app()->getLocale();

        $page = request('page', 1);
        $categoryId = request('category_id');

        $posts = $this->contentCache->remember(
            'post',
            $locale,
            'index.cat.'.($categoryId ?? 'all').'.page.'.$page,
            function () use ($locale, $categoryId) {
                return Post::published()
                    ->with(['translations', 'category.translations', 'media'])
                    ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
                    ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                    ->orderByDesc('published_at')
                    ->paginate(12)
                    ->through(fn (Post $post) => $this->presenter->card(
                        $this->publishedVersions->forPublicDisplay($post),
                        $locale,
                    ));
            },
        );

        $categories = $this->contentCache->remember('post', $locale, 'categories', function () use ($locale) {
            return Category::with(['translations'])
                ->get()
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->translation($locale)?->name,
                ])
                ->all();
        });

        return Inertia::render('public/news/index', [
            'posts' => $posts,
            'categories' => $categories,
            'filters' => [
                'category_id' => $categoryId,
            ],
        ]);
    }

    /**
     * A single published material resolved by its per-locale slug (ТЗ §6.2).
     */
    public function show(string $locale, string $slug): Response
    {
        $appLocale = app()->getLocale();

        $data = $this->contentCache->remember('post', $appLocale, "show.{$slug}", function () use ($appLocale, $slug) {
            $postId = $this->publishedVersions->resolvePublishedPostId($slug);

            if ($postId === null) {
                return null;
            }

            $post = Post::published()
                ->whereKey($postId)
                ->with(['category.translations', 'media', 'author', 'translations', 'tags.translations'])
                ->first();

            if ($post === null) {
                return null;
            }

            $post = $this->publishedVersions->forPublicDisplay($post);

            return $this->presenter->present($post, $appLocale);
        });

        abort_if($data === null, 404);

        return Inertia::render('public/news/show', $data);
    }
}
