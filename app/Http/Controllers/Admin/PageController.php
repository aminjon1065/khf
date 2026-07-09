<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
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
    private const SORTABLE = ['sort_order', 'status', 'created_at', 'updated_at'];

    public function __construct(
        private HtmlSanitizer $sanitizer,
        private BlockSanitizer $blockSanitizer,
        private TaxonomyService $taxonomies,
    ) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $filters = $this->listFilters($request, 'sort_order', 'asc');

        $pages = $this->paginateTranslatable(
            Page::query()->with('translations'),
            $request,
            self::SORTABLE,
            'sort_order',
            'asc',
            fn (Page $page) => $this->toRow($page, $locale),
        );

        return Inertia::render('admin/pages/index', [
            'pages' => $pages,
            'filters' => $filters,
            'trashedCount' => Page::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $locale = app()->getLocale();

        $pages = Page::onlyTrashed()
            ->with('translations')
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->through(fn (Page $page) => $this->toRow($page, $locale));

        return Inertia::render('admin/pages/trash', ['pages' => $pages]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/pages/form', $this->formData(null));
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

        return to_route('admin.pages.index');
    }

    public function edit(Page $page): Response
    {
        $page->load(['translations', 'media', 'tags.translations']);

        return Inertia::render('admin/pages/form', $this->formData($page));
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

        return to_route('admin.pages.index');
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
        return $this->moveToTrash($page, 'admin.pages.index', __('Page moved to trash.'));
    }

    public function restore(Page $page): RedirectResponse
    {
        return $this->restoreFromTrash($page, 'admin.pages.trash', __('Page restored.'));
    }

    public function forceDelete(Page $page): RedirectResponse
    {
        return $this->permanentlyDelete($page, 'admin.pages.trash', __('Page permanently deleted.'));
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
    private function toRow(Page $page, string $locale): array
    {
        return [
            'id' => $page->id,
            'title' => $page->translation($locale)?->title ?? '—',
            'status' => $page->status->value,
            'status_label' => $page->status->label(),
            'locales' => $page->translatedLocales(),
            'updated_at' => $page->updated_at?->toDateString(),
            'deleted_at' => $page->deleted_at?->toDateString(),
        ];
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

        return [
            'page' => $page ? [
                'id' => $page->id,
                'parent_id' => $page->parent_id,
                'status' => $page->status->value,
                'sort_order' => $page->sort_order,
                'is_home' => $page->is_home,
                'tag_ids' => $page->tags->pluck('id')->all(),
                'cover_url' => $page->getFirstMediaUrl(Page::COVER_COLLECTION, 'thumb') ?: null,
                'translations' => $translations,
            ] : null,
            'locales' => $this->localeOptions(),
            ...$this->publicationFormMeta($page?->status),
            ...$this->blueprintFormProps('page'),
            ...$this->blocksetFormProps('page'),
            'fieldOptions' => array_merge(
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
            'publicUrls' => $page ? PublicContentUrls::forPage($page) : [],
            'previewUrls' => $page ? app(PreviewUrls::class)->forPage($page->id) : [],
            'hasUnpublishedChanges' => $page?->hasUnpublishedChanges() ?? false,
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
                'content' => $this->sanitizedHtml($translation['content'] ?? null, $this->sanitizer),
                'blocks' => $this->blockSanitizer->sanitize($translation['blocks'] ?? null),
            ],
        );
    }
}
