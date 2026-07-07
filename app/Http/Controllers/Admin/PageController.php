<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Concerns\SyncsCoverFromLibrary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Language;
use App\Models\Page;
use App\Support\BlockSanitizer;
use App\Support\HtmlSanitizer;
use App\Support\PublicationScheduler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    use SyncsCoverFromLibrary;

    /** @var list<string> */
    private const SORTABLE = ['sort_order', 'status', 'created_at', 'updated_at'];

    public function __construct(
        private HtmlSanitizer $sanitizer,
        private BlockSanitizer $blockSanitizer,
    ) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), self::SORTABLE, true)
            ? (string) $request->string('sort')
            : 'sort_order';
        $direction = (string) $request->string('direction') === 'desc' ? 'desc' : 'asc';

        $pages = Page::query()
            ->with('translations')
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Page $page) => $this->toRow($page, $locale));

        return Inertia::render('admin/pages/index', [
            'pages' => $pages,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
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

        // If this page is set as home, reset others
        if ($page->is_home) {
            Page::where('id', '!=', $page->id)->update(['is_home' => false]);
        }

        $page->upsertTranslations($this->translationsPayload($data));
        $this->syncCover($request, $page, Page::COVER_COLLECTION);
        $page->saveRevision();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page created.')]);

        return to_route('admin.pages.index');
    }

    public function edit(Page $page): Response
    {
        $page->load(['translations', 'media']);

        return Inertia::render('admin/pages/form', $this->formData($page));
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();

        $page->update([
            'parent_id' => $data['parent_id'] ?? null,
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_home' => $data['is_home'] ?? false,
        ]);

        if ($page->is_home) {
            Page::where('id', '!=', $page->id)->update(['is_home' => false]);
        }

        $page->upsertTranslations($this->translationsPayload($data));
        $this->syncCover($request, $page, Page::COVER_COLLECTION);
        $page->saveRevision();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page updated.')]);

        return to_route('admin.pages.index');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page moved to trash.')]);

        return to_route('admin.pages.index');
    }

    public function restore(Page $page): RedirectResponse
    {
        $page->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page restored.')]);

        return to_route('admin.pages.trash');
    }

    public function forceDelete(Page $page): RedirectResponse
    {
        $page->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page permanently deleted.')]);

        return to_route('admin.pages.trash');
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
                'cover_url' => $page->getFirstMediaUrl(Page::COVER_COLLECTION, 'thumb') ?: null,
                'translations' => $translations,
            ] : null,
            'locales' => Language::active()
                ->map(fn (Language $language) => ['code' => $language->code, 'native_name' => $language->native_name])
                ->all(),
            'statuses' => PublicationScheduler::statusOptions(),
            'statusTransitions' => PublicationScheduler::transitionOptions(
                $page?->status ?? ContentStatus::Draft,
            ),
            'parents' => Page::query()
                ->when($page, fn (Builder $query) => $query->whereKeyNot($page->id))
                ->with('translations')
                ->get()
                ->map(fn (Page $parent) => ['id' => $parent->id, 'title' => $parent->translation()?->title ?? "#{$parent->id}"])
                ->all(),
            'defaultLocale' => Language::defaultCode(),
        ];
    }

    /**
     * Build the `[locale => attributes]` payload from validated data, skipping empty locales.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationsPayload(array $data): array
    {
        return collect($data['translations'] ?? [])
            ->filter(fn (array $translation) => filled($translation['title'] ?? null))
            ->map(fn (array $translation) => [
                'title' => $translation['title'],
                'slug' => $translation['slug'] ?? Str::tajikSlug($translation['title']),
                'content' => $this->sanitizer->clean($translation['content'] ?? null),
                'blocks' => $this->blockSanitizer->sanitize($translation['blocks'] ?? null),
                'seo_title' => $translation['seo_title'] ?? null,
                'seo_description' => $translation['seo_description'] ?? null,
            ])
            ->all();
    }
}
