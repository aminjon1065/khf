<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
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
use App\Http\Requests\Admin\AutosavePageRequest;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use App\Services\Admin\ContentEntryService;
use App\Services\Cms\TaxonomyService;
use App\Support\PreviewUrls;
use App\Support\PublicContentUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
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

    public function __construct(
        private ContentEntryService $entries,
        private TaxonomyService $taxonomies,
    ) {}

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('page');
    }

    public function trash(): RedirectResponse
    {
        return $this->redirectToContentBrowserTrash('page');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/editorial-form', $this->formData(null));
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /** @var Page $page */
        $page = $this->entries->store('page', $data, saveRevision: false);

        $this->ensureSingleHomepage($page);
        $this->taxonomies->syncForModel($page, $data);
        $this->syncCover($request, $page, Page::COVER_COLLECTION);
        $this->syncPublishedSnapshot(
            $page,
            ContentStatus::Draft,
            ContentStatus::from($data['status']),
        );
        $this->saveContentRevision($page);
        $this->flashContentSaved(__('Page created.'));

        return $this->toContentBrowser('page');
    }

    public function edit(Page $page): Response
    {
        $page->load(['translations', 'media', 'tags.translations']);

        return Inertia::render('admin/content/editorial-form', $this->formData($page));
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();
        $previousStatus = $page->status;

        $this->entries->update('page', $page, $data, saveRevision: false);
        $this->ensureSingleHomepage($page);
        $this->taxonomies->syncForModel($page, $data);
        $this->syncCover($request, $page, Page::COVER_COLLECTION);
        $this->syncPublishedSnapshot(
            $page,
            $previousStatus,
            ContentStatus::from($data['status']),
        );
        $this->saveContentRevision($page);
        $this->flashContentSaved(__('Page updated.'));

        return $this->toContentBrowser('page');
    }

    public function autosave(AutosavePageRequest $request, Page $page): JsonResponse
    {
        $data = $request->validated();

        $this->entries->update('page', $page, $data, saveRevision: false);
        $this->ensureSingleHomepage($page);
        $this->taxonomies->syncForModel($page, $data);

        return $this->autosaveResponse($page);
    }

    public function publishVersion(Page $page): RedirectResponse
    {
        abort_if($page->status !== ContentStatus::Published, 422);

        $this->publishWorkingCopy($page);
        $this->flashContentSaved('Опубликованная версия обновлена.');

        return redirect()->route('admin.pages.edit', $page);
    }

    public function destroy(Page $page): RedirectResponse
    {
        return $this->moveToTrash($page, 'admin.content.index', __('Page moved to trash.'), 'page');
    }

    public function restore(Page $page): RedirectResponse
    {
        return $this->restoreFromTrash($page, 'admin.content.index', __('Page restored.'), ['type' => 'page', 'trashed' => 1]);
    }

    public function forceDelete(Page $page): RedirectResponse
    {
        return $this->permanentlyDelete($page, 'admin.content.index', __('Page permanently deleted.'), ['type' => 'page', 'trashed' => 1]);
    }

    private function ensureSingleHomepage(Page $page): void
    {
        if ($page->is_home) {
            Page::where('id', '!=', $page->id)->update(['is_home' => false]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Page $page): array
    {
        $entry = null;

        if ($page) {
            $entry = $this->entries->entryArray($page, 'page');
            $entry['tag_ids'] = $page->tags->pluck('id')->all();
            $entry['cover_url'] = $page->getFirstMediaUrl(Page::COVER_COLLECTION, 'thumb') ?: null;
        }

        return $this->editorialEntryFormProps(
            'page',
            $entry,
            array_merge(
                [
                    'parent_id' => Page::query()
                        ->when($page, fn (Builder $query) => $query->whereKeyNot($page->id))
                        ->with('translations')
                        ->get()
                        ->map(fn (Page $parent) => ['id' => $parent->id, 'name' => $parent->translation()?->title ?? "#{$parent->id}"])
                        ->all(),
                ],
                $this->taxonomies->fieldOptionsForCollection('page'),
            ),
            [
                'publicUrls' => $page ? PublicContentUrls::forPage($page) : [],
                'previewUrls' => $page ? app(PreviewUrls::class)->forPage($page->id) : [],
                'hasUnpublishedChanges' => $page?->hasUnpublishedChanges() ?? false,
                'blocksetHandle' => 'page',
            ],
        );
    }
}
