<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Http\Controllers\Admin\Concerns\AutosavesEditorialContent;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\BuildsTranslationPayload;
use App\Http\Controllers\Admin\Concerns\ListsTranslatableContent;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\PublishesWorkingCopy;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Concerns\SyncsCoverFromLibrary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AutosavePostRequest;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Support\HtmlSanitizer;
use App\Support\PreviewUrls;
use App\Support\PublicationScheduler;
use App\Support\PublicContentUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use AutosavesEditorialContent;
    use BuildsCmsFormData;
    use BuildsTranslationPayload;
    use ListsTranslatableContent;
    use ManagesSoftDeletableContent;
    use ProvidesBlueprintForm;
    use PublishesWorkingCopy;
    use SavesContentRevisions;
    use SyncsCoverFromLibrary;

    /** @var list<string> */
    private const SORTABLE = ['status', 'type', 'published_at', 'created_at'];

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $filters = $this->listFilters($request, 'published_at', 'desc');

        $posts = $this->paginateTranslatable(
            Post::query()->with(['translations', 'category.translations', 'media']),
            $request,
            self::SORTABLE,
            'published_at',
            'desc',
            fn (Post $post) => $this->toRow($post, $locale),
        );

        return Inertia::render('admin/posts/index', [
            'posts' => $posts,
            'filters' => $filters,
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
        $data = PublicationScheduler::normalize($request->validated());

        $post = Post::create([
            'type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'author_id' => $request->user()->id,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? null,
            'unpublished_at' => $data['unpublished_at'] ?? null,
        ]);
        $post->upsertTranslations($this->translationsPayload($data));
        $post->tags()->sync($data['tag_ids'] ?? []);
        $this->syncCover($request, $post, Post::COVER_COLLECTION);
        $this->syncPublishedSnapshot(
            $post,
            ContentStatus::Draft,
            ContentStatus::from($data['status']),
        );
        $this->saveContentRevision($post);
        $this->flashContentSaved(__('Post created.'));

        return to_route('admin.posts.index');
    }

    public function edit(Post $post): Response
    {
        $post->load(['translations', 'media', 'tags.translations']);

        return Inertia::render('admin/posts/form', $this->formData($post));
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $data = PublicationScheduler::normalize($request->validated());
        $previousStatus = $post->status;

        $post->update([
            'type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? null,
            'unpublished_at' => $data['unpublished_at'] ?? null,
        ]);
        $post->upsertTranslations($this->translationsPayload($data));
        $post->tags()->sync($data['tag_ids'] ?? []);
        $this->syncCover($request, $post, Post::COVER_COLLECTION);
        $this->syncPublishedSnapshot(
            $post,
            $previousStatus,
            ContentStatus::from($data['status']),
        );
        $this->saveContentRevision($post);
        $this->flashContentSaved(__('Post updated.'));

        return to_route('admin.posts.index');
    }

    public function autosave(AutosavePostRequest $request, Post $post): JsonResponse
    {
        $data = $request->validated();

        $attributes = [];

        if (array_key_exists('type', $data)) {
            $attributes['type'] = $data['type'];
        }

        if (array_key_exists('category_id', $data)) {
            $attributes['category_id'] = $data['category_id'];
        }

        if (array_key_exists('published_at', $data)) {
            $attributes['published_at'] = PublicationScheduler::parseDateTime($data['published_at']);
        }

        if (array_key_exists('unpublished_at', $data)) {
            $attributes['unpublished_at'] = PublicationScheduler::parseDateTime($data['unpublished_at']);
        }

        if ($attributes !== []) {
            $post->update($attributes);
        }

        $post->upsertTranslations($this->translationsPayload($data));

        if (array_key_exists('tag_ids', $data)) {
            $post->tags()->sync($data['tag_ids'] ?? []);
        }

        return $this->autosaveResponse($post);
    }

    public function publishVersion(Post $post): RedirectResponse
    {
        abort_if($post->status !== ContentStatus::Published, 422);

        $this->publishWorkingCopy($post);
        $this->flashContentSaved('Опубликованная версия обновлена.');

        return redirect()->route('admin.posts.edit', $post);
    }

    public function destroy(Post $post): RedirectResponse
    {
        return $this->moveToTrash($post, 'admin.posts.index', __('Post moved to trash.'));
    }

    public function restore(Post $post): RedirectResponse
    {
        return $this->restoreFromTrash($post, 'admin.posts.trash', __('Post restored.'));
    }

    public function forceDelete(Post $post): RedirectResponse
    {
        return $this->permanentlyDelete($post, 'admin.posts.trash', __('Post permanently deleted.'));
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
            'locales' => $post->translatedLocales(),
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
                'tag_ids' => $post->tags->pluck('id')->all(),
                'status' => $post->status->value,
                'published_at' => $post->published_at?->format('Y-m-d\TH:i'),
                'unpublished_at' => $post->unpublished_at?->format('Y-m-d\TH:i'),
                'cover_url' => $post->getFirstMediaUrl(Post::COVER_COLLECTION, 'thumb') ?: null,
                'translations' => $translations,
            ] : null,
            'locales' => $this->localeOptions(),
            ...$this->publicationFormMeta($post?->status),
            ...$this->blueprintFormProps('post'),
            'fieldOptions' => [
                'type' => array_map(
                    fn (PostType $type) => ['value' => $type->value, 'label' => $type->label()],
                    PostType::cases(),
                ),
                'category_id' => Category::query()
                    ->with('translations')
                    ->get()
                    ->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->translation($locale)?->name ?? "#{$category->id}"])
                    ->all(),
                'tag_ids' => Tag::query()
                    ->with('translations')
                    ->get()
                    ->map(fn (Tag $tag) => ['id' => $tag->id, 'name' => $tag->translation($locale)?->name ?? "#{$tag->id}"])
                    ->all(),
            ],
            'publicUrls' => $post ? PublicContentUrls::forPost($post) : [],
            'previewUrls' => $post ? app(PreviewUrls::class)->forPost($post->id) : [],
            'hasUnpublishedChanges' => $post?->hasUnpublishedChanges() ?? false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationsPayload(array $data): array
    {
        return $this->buildTranslationPayload(
            $data,
            fn (array $translation) => [
                ...$this->baseTranslationFields($translation, $this->sanitizer),
                'excerpt' => $translation['excerpt'] ?? null,
                'body' => $this->sanitizedHtml($translation['body'] ?? null, $this->sanitizer),
            ],
        );
    }
}
