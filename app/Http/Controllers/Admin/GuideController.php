<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\GuideAudience;
use App\Enums\IncidentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGuideRequest;
use App\Http\Requests\Admin\UpdateGuideRequest;
use App\Models\Guide;
use App\Models\GuideTranslation;
use App\Models\Language;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
{
    /** @var list<string> */
    private const SORTABLE = ['hazard_type', 'audience', 'status', 'sort_order', 'created_at'];

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), self::SORTABLE, true)
            ? (string) $request->string('sort')
            : 'sort_order';
        $direction = (string) $request->string('direction') === 'desc' ? 'desc' : 'asc';

        $guides = Guide::query()
            ->with(['translations', 'media'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Guide $guide) => $this->toRow($guide, $locale));

        return Inertia::render('admin/guides/index', [
            'guides' => $guides,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'trashedCount' => Guide::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $locale = app()->getLocale();

        $guides = Guide::onlyTrashed()
            ->with('translations')
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->through(fn (Guide $guide) => $this->toRow($guide, $locale));

        return Inertia::render('admin/guides/trash', ['guides' => $guides]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/guides/form', $this->formData(null));
    }

    public function store(StoreGuideRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $guide = Guide::create($this->attributes($data));
        $guide->upsertTranslations($this->translationsPayload($data, $guide->id));
        $this->syncFiles($request, $guide);
        $guide->saveRevision();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Guide created.')]);

        return to_route('admin.guides.index');
    }

    public function edit(Guide $guide): Response
    {
        $guide->load(['translations', 'media']);

        return Inertia::render('admin/guides/form', $this->formData($guide));
    }

    public function update(UpdateGuideRequest $request, Guide $guide): RedirectResponse
    {
        $data = $request->validated();

        $guide->update($this->attributes($data));
        $guide->upsertTranslations($this->translationsPayload($data, $guide->id));
        $this->syncFiles($request, $guide);
        $guide->saveRevision();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Guide updated.')]);

        return to_route('admin.guides.index');
    }

    public function destroy(Guide $guide): RedirectResponse
    {
        $guide->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Guide moved to trash.')]);

        return to_route('admin.guides.index');
    }

    public function restore(Guide $guide): RedirectResponse
    {
        $guide->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Guide restored.')]);

        return to_route('admin.guides.trash');
    }

    public function forceDelete(Guide $guide): RedirectResponse
    {
        $guide->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Guide permanently deleted.')]);

        return to_route('admin.guides.trash');
    }

    private function syncFiles(Request $request, Guide $guide): void
    {
        foreach ($request->file('files') ?? [] as $file) {
            $guide->addMedia($file)->toMediaCollection(Guide::FILES_COLLECTION);
        }

        $removeIds = array_map('intval', $request->input('remove_files', []) ?? []);

        if ($removeIds !== []) {
            $guide->getMedia(Guide::FILES_COLLECTION)
                ->whereIn('id', $removeIds)
                ->each->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'hazard_type' => $data['hazard_type'] ?? null,
            'audience' => $data['audience'],
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Guide $guide, string $locale): array
    {
        return [
            'id' => $guide->id,
            'title' => $guide->translation($locale)?->title ?? '—',
            'hazard_type' => $guide->hazard_type?->value,
            'hazard_label' => $guide->hazard_type?->label(),
            'audience' => $guide->audience->value,
            'audience_label' => $guide->audience->label(),
            'status' => $guide->status->value,
            'status_label' => $guide->status->label(),
            'locales' => $guide->translatedLocales(),
            'files_count' => $guide->relationLoaded('media') ? $guide->getMedia(Guide::FILES_COLLECTION)->count() : 0,
            'deleted_at' => $guide->deleted_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Guide $guide): array
    {
        $translations = [];
        $files = [];

        if ($guide) {
            foreach ($guide->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'summary' => $translation->summary,
                    'content' => $translation->content,
                    'seo_title' => $translation->seo_title,
                    'seo_description' => $translation->seo_description,
                ];
            }

            $files = $guide->getMedia(Guide::FILES_COLLECTION)
                ->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'size' => $media->humanReadableSize,
                    'url' => route('guides.download', [
                        'locale' => app()->getLocale(),
                        'guide' => $guide->id,
                        'media' => $media->id,
                    ]),
                ])
                ->all();
        }

        return [
            'guide' => $guide ? [
                'id' => $guide->id,
                'hazard_type' => $guide->hazard_type?->value,
                'audience' => $guide->audience->value,
                'status' => $guide->status->value,
                'sort_order' => $guide->sort_order,
                'translations' => $translations,
                'files' => $files,
            ] : null,
            'hazardTypes' => array_map(
                fn (IncidentType $type) => ['value' => $type->value, 'label' => $type->label()],
                IncidentType::cases(),
            ),
            'audiences' => GuideAudience::options(),
            'statuses' => array_map(
                fn (ContentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ContentStatus::cases(),
            ),
            'locales' => Language::active()
                ->map(fn (Language $language) => ['code' => $language->code, 'native_name' => $language->native_name])
                ->all(),
            'defaultLocale' => Language::defaultCode(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationsPayload(array $data, ?int $guideId): array
    {
        return collect($data['translations'] ?? [])
            ->filter(fn (array $translation) => filled($translation['title'] ?? null))
            ->map(fn (array $translation, string $locale) => [
                'title' => $translation['title'],
                'slug' => $this->uniqueSlug(
                    filled($translation['slug'] ?? null) ? $translation['slug'] : Str::tajikSlug($translation['title']),
                    $locale,
                    $guideId,
                ),
                'summary' => $translation['summary'] ?? null,
                'content' => $this->sanitizer->clean($translation['content'] ?? null),
                'seo_title' => $translation['seo_title'] ?? null,
                'seo_description' => $translation['seo_description'] ?? null,
            ])
            ->all();
    }

    /**
     * Guarantee a (locale, slug) unique slug — auto-generated slugs can collapse to the same value
     * (e.g. Tajik titles that `Str::slug` strips to empty), which the DB unique index would reject.
     * Empty bases fall back to `guide`, and collisions get a numeric suffix.
     */
    private function uniqueSlug(string $base, string $locale, ?int $exceptGuideId): string
    {
        $base = $base !== '' ? $base : 'guide';
        $slug = $base;
        $suffix = 2;

        while (GuideTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->when($exceptGuideId !== null, fn ($query) => $query->where('guide_id', '!=', $exceptGuideId))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
