<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Http\Controllers\Admin\Concerns\AutosavesEditorialContent;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\BuildsEditorialEntryFormProps;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\PublishesWorkingCopy;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Concerns\SyncsCoverFromLibrary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AutosavePostRequest;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Admin\ContentEntryService;
use App\Support\PreviewUrls;
use App\Support\PublicContentUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use AutosavesEditorialContent;
    use BuildsCmsFormData;
    use BuildsEditorialEntryFormProps;
    use ManagesSoftDeletableContent;
    use ProvidesBlueprintForm;
    use PublishesWorkingCopy;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;
    use SyncsCoverFromLibrary;

    public function __construct(private ContentEntryService $entries) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('post');
    }

    public function trash(): RedirectResponse
    {
        return $this->redirectToContentBrowserTrash('post');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/editorial-form', $this->formData(null));
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /** @var Post $post */
        $post = $this->entries->store('post', $data, [
            'author_id' => $request->user()->id,
        ], saveRevision: false);

        $post->tags()->sync($data['tag_ids'] ?? []);
        $this->syncCover($request, $post, Post::COVER_COLLECTION);
        $this->syncPublishedSnapshot(
            $post,
            ContentStatus::Draft,
            ContentStatus::from($data['status']),
        );
        $this->saveContentRevision($post);
        $this->flashContentSaved(__('Post created.'));

        return $this->toContentBrowser('post');
    }

    public function edit(Post $post): Response
    {
        $post->load(['translations', 'media', 'tags.translations']);

        return Inertia::render('admin/content/editorial-form', $this->formData($post));
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();
        $previousStatus = $post->status;

        $this->entries->update('post', $post, $data, saveRevision: false);
        $post->tags()->sync($data['tag_ids'] ?? []);
        $this->syncCover($request, $post, Post::COVER_COLLECTION);
        $this->syncPublishedSnapshot(
            $post,
            $previousStatus,
            ContentStatus::from($data['status']),
        );
        $this->saveContentRevision($post);
        $this->flashContentSaved(__('Post updated.'));

        return $this->toContentBrowser('post');
    }

    public function autosave(AutosavePostRequest $request, Post $post): JsonResponse
    {
        $data = $request->validated();

        $this->entries->update('post', $post, $data, saveRevision: false);

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
        return $this->moveToTrash($post, 'admin.content.index', __('Post moved to trash.'), 'post');
    }

    public function restore(Post $post): RedirectResponse
    {
        return $this->restoreFromTrash($post, 'admin.content.index', __('Post restored.'), ['type' => 'post', 'trashed' => 1]);
    }

    public function forceDelete(Post $post): RedirectResponse
    {
        return $this->permanentlyDelete($post, 'admin.content.index', __('Post permanently deleted.'), ['type' => 'post', 'trashed' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Post $post): array
    {
        $locale = app()->getLocale();
        $entry = null;

        if ($post) {
            $entry = $this->entries->entryArray($post, 'post');
            $entry['tag_ids'] = $post->tags->pluck('id')->all();
            $entry['cover_url'] = $post->getFirstMediaUrl(Post::COVER_COLLECTION, 'thumb') ?: null;
        }

        return $this->editorialEntryFormProps(
            'post',
            $entry,
            [
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
            [
                'publicUrls' => $post ? PublicContentUrls::forPost($post) : [],
                'previewUrls' => $post ? app(PreviewUrls::class)->forPost($post->id) : [],
                'hasUnpublishedChanges' => $post?->hasUnpublishedChanges() ?? false,
            ],
        );
    }
}
