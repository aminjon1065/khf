<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Admin\Concerns\AutosavesEditorialContent;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\BuildsEditorialEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsTranslationPayload;
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
use App\Services\Cms\TaxonomyService;
use App\Support\BlockSanitizer;
use App\Support\HtmlSanitizer;
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
    use BuildsTranslationPayload;
    use ManagesSoftDeletableContent;
    use ProvidesBlueprintForm;
    use PublishesWorkingCopy;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;
    use SyncsCoverFromLibrary;

    public function __construct(
        private HtmlSanitizer $sanitizer,
        private BlockSanitizer $blockSanitizer,
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

        $page = Page::create([
            'parent_id' => $data['parent_id'] ?? null,
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_home' => $data['is_home'] ?? false,
        ]);

        $this->ensureSingleHomepage($page);

        $page->upsertTranslations($this->translationsPayload($data));
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

        $page->update([
            'parent_id' => $data['parent_id'] ?? null,
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_home' => $data['is_home'] ?? false,
        ]);

        $this->ensureSingleHomepage($page);

        $page->upsertTranslations($this->translationsPayload($data));
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

        $page->update([
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_home' => $data['is_home'] ?? false,
        ]);

        $this->ensureSingleHomepage($page);
        $page->upsertTranslations($this->translationsPayload($data));
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
        $translations = [];

        if ($page) {
            foreach ($page->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'content' => $translation->content,
                    'blocks' => $translation->blocks ?? [],
                    'seo_title' => $translation->seo_title,
                    'seo_description' => $translation->seo_description,
                ];
            }
        }

        return $this->editorialEntryFormProps(
            'page',
            $page ? [
                'id' => $page->id,
                'parent_id' => $page->parent_id,
                'status' => $page->status->value,
                'sort_order' => $page->sort_order,
                'is_home' => $page->is_home,
                'tag_ids' => $page->tags->pluck('id')->all(),
                'cover_url' => $page->getFirstMediaUrl(Page::COVER_COLLECTION, 'thumb') ?: null,
                'translations' => $translations,
            ] : null,
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
                'content' => $this->sanitizedHtml($translation['content'] ?? null, $this->sanitizer),
                'blocks' => $this->blockSanitizer->sanitize($translation['blocks'] ?? null),
            ],
        );
    }
}
