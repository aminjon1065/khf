<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /** @var list<string> */
    private const SORTABLE = ['status', 'type', 'published_at', 'created_at'];

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), self::SORTABLE, true)
            ? (string) $request->string('sort')
            : 'published_at';
        $direction = (string) $request->string('direction') === 'asc' ? 'asc' : 'desc';

        $posts = Post::query()
            ->with(['translations', 'category.translations', 'media'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Post $post) => $this->toRow($post, $locale));

        return Inertia::render('admin/posts/index', [
            'posts' => $posts,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'trashedCount' => Post::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $locale = app()->getLocale();

        $posts = Post::onlyTrashed()
            ->with(['translations', 'media'])
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->through(fn (Post $post) => $this->toRow($post, $locale));

        return Inertia::render('admin/posts/trash', ['posts' => $posts]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/posts/form', $this->formData(null));
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $post = Post::create([
            'type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'author_id' => $request->user()->id,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? null,
        ]);
        $post->upsertTranslations($this->translationsPayload($data));
        $this->syncCover($request, $post);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post created.')]);

        return to_route('admin.posts.index');
    }

    public function edit(Post $post): Response
    {
        $post->load(['translations', 'media']);

        return Inertia::render('admin/posts/form', $this->formData($post));
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();

        $post->update([
            'type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? null,
        ]);
        $post->upsertTranslations($this->translationsPayload($data));
        $this->syncCover($request, $post);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post updated.')]);

        return to_route('admin.posts.index');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post moved to trash.')]);

        return to_route('admin.posts.index');
    }

    public function restore(Post $post): RedirectResponse
    {
        $post->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post restored.')]);

        return to_route('admin.posts.trash');
    }

    public function forceDelete(Post $post): RedirectResponse
    {
        $post->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post permanently deleted.')]);

        return to_route('admin.posts.trash');
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Post $post, string $locale): array
    {
        return [
            'id' => $post->id,
            'title' => $post->translation($locale)?->title ?? '—',
            'type' => $post->type->value,
            'type_label' => $post->type->label(),
            'category' => $post->category?->translation($locale)?->name,
            'status' => $post->status->value,
            'status_label' => $post->status->label(),
            'cover_url' => $post->getFirstMediaUrl(Post::COVER_COLLECTION, 'thumb') ?: null,
            'translated_locales' => $post->translatedLocales(),
            'published_at' => $post->published_at?->toDateString(),
            'deleted_at' => $post->deleted_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Post $post): array
    {
        $locale = app()->getLocale();
        $translations = [];

        if ($post) {
            foreach ($post->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'excerpt' => $translation->excerpt,
                    'body' => $translation->body,
                    'seo_title' => $translation->seo_title,
                    'seo_description' => $translation->seo_description,
                ];
            }
        }

        return [
            'post' => $post ? [
                'id' => $post->id,
                'type' => $post->type->value,
                'category_id' => $post->category_id,
                'status' => $post->status->value,
                'published_at' => $post->published_at?->format('Y-m-d\TH:i'),
                'cover_url' => $post->getFirstMediaUrl(Post::COVER_COLLECTION, 'thumb') ?: null,
                'translations' => $translations,
            ] : null,
            'locales' => Language::active()
                ->map(fn (Language $language) => ['code' => $language->code, 'native_name' => $language->native_name])
                ->all(),
            'types' => array_map(
                fn (PostType $type) => ['value' => $type->value, 'label' => $type->label()],
                PostType::cases(),
            ),
            'statuses' => array_map(
                fn (ContentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ContentStatus::cases(),
            ),
            'categories' => Category::query()
                ->with('translations')
                ->get()
                ->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->translation($locale)?->name ?? "#{$category->id}"])
                ->all(),
            'defaultLocale' => Language::defaultCode(),
        ];
    }

    /**
     * Add an uploaded cover, or clear it when the "remove" flag is set (ТЗ §6.2, §7.7).
     */
    private function syncCover(Request $request, Post $post): void
    {
        if ($request->hasFile('cover')) {
            $post->addMediaFromRequest('cover')->toMediaCollection(Post::COVER_COLLECTION);
        } elseif ($request->boolean('remove_cover')) {
            $post->clearMediaCollection(Post::COVER_COLLECTION);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationsPayload(array $data): array
    {
        return collect($data['translations'] ?? [])
            ->filter(fn (array $translation) => filled($translation['title'] ?? null))
            ->map(fn (array $translation) => [
                'title' => $translation['title'],
                'slug' => $translation['slug'] ?? Str::slug($translation['title']),
                'excerpt' => $translation['excerpt'] ?? null,
                'body' => $this->sanitizer->clean($translation['body'] ?? null),
                'seo_title' => $translation['seo_title'] ?? null,
                'seo_description' => $translation['seo_description'] ?? null,
            ])
            ->all();
    }
}
