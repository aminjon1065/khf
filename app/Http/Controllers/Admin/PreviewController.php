<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Services\Public\PageShowPresenter;
use App\Services\Public\PostShowPresenter;
use App\Support\LocaleUrls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders public-facing Inertia pages for CMS live preview (draft / moderation content).
 */
class PreviewController extends Controller
{
    public function show(
        Request $request,
        string $type,
        int $id,
        PageShowPresenter $pagePresenter,
        PostShowPresenter $postPresenter,
    ): Response {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);

        return match ($type) {
            'page' => $this->previewPage($id, $locale, $pagePresenter),
            'post' => $this->previewPost($id, $locale, $postPresenter),
            default => abort(404),
        };
    }

    private function resolveLocale(Request $request): string
    {
        $locale = $request->string('locale')->toString();
        $supported = app(LocaleUrls::class)->supportedCodes();

        if ($locale !== '' && in_array($locale, $supported, true)) {
            return $locale;
        }

        return app(LocaleUrls::class)->defaultCode();
    }

    private function previewPage(int $id, string $locale, PageShowPresenter $presenter): Response
    {
        Gate::authorize(Permission::ManagePages->value);

        $page = Page::query()
            ->with(['translations', 'media'])
            ->findOrFail($id);

        return Inertia::render(
            'public/pages/show',
            $presenter->present($page, $locale, preview: true),
        );
    }

    private function previewPost(int $id, string $locale, PostShowPresenter $presenter): Response
    {
        Gate::authorize(Permission::ManagePosts->value);

        $post = Post::query()
            ->with(['category.translations', 'media', 'author', 'translations', 'tags.translations'])
            ->findOrFail($id);

        return Inertia::render(
            'public/news/show',
            $presenter->present($post, $locale, preview: true),
        );
    }
}
