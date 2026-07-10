<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GuideAudience;
use App\Enums\IncidentType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ListsTranslatableContent;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGuideRequest;
use App\Http\Requests\Admin\UpdateGuideRequest;
use App\Models\Guide;
use App\Models\GuideTranslation;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
{
    use BuildsCmsFormData;
    use ListsTranslatableContent;
    use ManagesSoftDeletableContent;
    use ProvidesBlueprintForm;
    use SavesContentRevisions;

    /** @var list<string> */
    private const SORTABLE = ['hazard_type', 'audience', 'status', 'sort_order', 'created_at'];

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $filters = $this->listFilters($request, 'sort_order', 'asc');

        $guides = $this->paginateTranslatable(
            Guide::query()->with(['translations', 'media']),
            $request,
            self::SORTABLE,
            'sort_order',
            'asc',
            fn (Guide $guide, string $locale) => $this->toRow($guide, $locale),
        );

        return Inertia::render('admin/guides/index', [
            'guides' => $guides,
            'filters' => $filters,
            'trashedCount' => Guide::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $guides = $this->paginateTrashed(
            Guide::onlyTrashed()->with('translations'),
            fn (Guide $guide, string $locale) => $this->toRow($guide, $locale),
        );

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
        $this->saveContentRevision($guide);
        $this->flashContentSaved(__('Guide created.'));

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
        $this->saveContentRevision($guide);
        $this->flashContentSaved(__('Guide updated.'));

        return to_route('admin.guides.index');
    }

    public function destroy(Guide $guide): RedirectResponse
    {
        return $this->moveToTrash($guide, 'admin.guides.index', __('Guide moved to trash.'));
    }

    public function restore(Guide $guide): RedirectResponse
    {
        return $this->restoreFromTrash($guide, 'admin.guides.trash', __('Guide restored.'));
    }

    public function forceDelete(Guide $guide): RedirectResponse
    {
        return $this->permanentlyDelete($guide, 'admin.guides.trash', __('Guide permanently deleted.'));
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
            'hazard_type' => filled($data['hazard_type'] ?? null) ? $data['hazard_type'] : null,
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
                'hazard_type' => $guide->hazard_type?->value ?? '',
                'audience' => $guide->audience->value,
                'status' => $guide->status->value,
                'sort_order' => $guide->sort_order,
                'translations' => $translations,
            ] : null,
            ...$this->publicationFormMeta($guide?->status),
            ...$this->blueprintFormProps('guide'),
            'fieldOptions' => [
                'hazard_type' => array_merge(
                    [['value' => '', 'label' => 'Без привязки']],
                    array_map(
                        fn (IncidentType $type) => ['value' => $type->value, 'label' => $type->label()],
                        IncidentType::cases(),
                    ),
                ),
                'audience' => GuideAudience::options(),
            ],
            'locales' => $this->localeOptions(),
            'existingFiles' => $files,
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
