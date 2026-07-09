<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenderType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ListsTranslatableContent;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenderRequest;
use App\Http\Requests\Admin\UpdateTenderRequest;
use App\Models\Tender;
use App\Support\HtmlSanitizer;
use App\Support\PublicationScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenderController extends Controller
{
    use BuildsCmsFormData;
    use ListsTranslatableContent;
    use ManagesSoftDeletableContent;
    use SavesContentRevisions;

    /** @var list<string> */
    private const SORTABLE = ['status', 'type', 'published_at', 'deadline_at', 'created_at'];

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $filters = $this->listFilters($request, 'published_at', 'desc');

        $tenders = $this->paginateTranslatable(
            Tender::query()->with('translations')->withCount('bids'),
            $request,
            self::SORTABLE,
            'published_at',
            'desc',
            fn (Tender $tender, string $locale) => $this->toRow($tender, $locale),
        );

        return Inertia::render('admin/tenders/index', [
            'tenders' => $tenders,
            'filters' => $filters,
            'trashedCount' => Tender::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $tenders = $this->paginateTrashed(
            Tender::onlyTrashed()->with('translations')->withCount('bids'),
            fn (Tender $tender, string $locale) => $this->toRow($tender, $locale),
        );

        return Inertia::render('admin/tenders/trash', ['tenders' => $tenders]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/tenders/form', $this->formData(null));
    }

    public function store(StoreTenderRequest $request): RedirectResponse
    {
        $data = PublicationScheduler::normalize($request->validated());

        $tender = Tender::create([
            'tender_number' => $data['tender_number'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'budget' => $data['budget'] ?? null,
            'lots_count' => $data['lots_count'],
            'published_at' => $data['published_at'] ?? null,
            'unpublished_at' => $data['unpublished_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);
        $tender->upsertTranslations($this->translationsPayload($data));
        $this->flashContentSaved(__('Tender created.'));

        return to_route('admin.tenders.index');
    }

    public function edit(Tender $tender): Response
    {
        $tender->load('translations');

        return Inertia::render('admin/tenders/form', $this->formData($tender));
    }

    public function update(UpdateTenderRequest $request, Tender $tender): RedirectResponse
    {
        $data = PublicationScheduler::normalize($request->validated());

        $tender->update([
            'tender_number' => $data['tender_number'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'budget' => $data['budget'] ?? null,
            'lots_count' => $data['lots_count'],
            'published_at' => $data['published_at'] ?? null,
            'unpublished_at' => $data['unpublished_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
        ]);
        $tender->upsertTranslations($this->translationsPayload($data));
        $this->flashContentSaved(__('Tender updated.'));

        return to_route('admin.tenders.index');
    }

    public function destroy(Tender $tender): RedirectResponse
    {
        return $this->moveToTrash($tender, 'admin.tenders.index', __('Tender moved to trash.'));
    }

    public function restore(Tender $tender): RedirectResponse
    {
        return $this->restoreFromTrash($tender, 'admin.tenders.trash', __('Tender restored.'));
    }

    public function forceDelete(Tender $tender): RedirectResponse
    {
        return $this->permanentlyDelete($tender, 'admin.tenders.trash', __('Tender permanently deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Tender $tender, string $locale): array
    {
        return [
            'id' => $tender->id,
            'title' => $tender->translation($locale)?->title ?? '—',
            'organizer' => $tender->translation($locale)?->organizer,
            'tender_number' => $tender->tender_number,
            'type' => $tender->type->value,
            'type_label' => $tender->type->label(),
            'status' => $tender->status->value,
            'status_label' => $tender->status->label(),
            'lots_count' => $tender->lots_count,
            'bids_count' => $tender->bids_count,
            'locales' => $tender->translatedLocales(),
            'published_at' => $tender->published_at?->toDateString(),
            'deadline_at' => $tender->deadline_at?->toDateString(),
            'is_open' => $tender->isOpen(),
            'deleted_at' => $tender->deleted_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Tender $tender): array
    {
        $translations = [];

        if ($tender) {
            foreach ($tender->translations as $translation) {
                $translations[$translation->locale] = [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'organizer' => $translation->organizer,
                    'summary' => $translation->summary,
                    'description' => $translation->description,
                    'requirements' => $translation->requirements,
                    'terms' => $translation->terms,
                    'seo_title' => $translation->seo_title,
                    'seo_description' => $translation->seo_description,
                ];
            }
        }

        return [
            'tender' => $tender ? [
                'id' => $tender->id,
                'tender_number' => $tender->tender_number,
                'type' => $tender->type->value,
                'status' => $tender->status->value,
                'budget' => $tender->budget,
                'lots_count' => $tender->lots_count,
                'published_at' => $tender->published_at?->format('Y-m-d\TH:i'),
                'unpublished_at' => $tender->unpublished_at?->format('Y-m-d\TH:i'),
                'deadline_at' => $tender->deadline_at?->format('Y-m-d'),
                'translations' => $translations,
            ] : null,
            'locales' => $this->localeOptions(),
            'tenderTypes' => TenderType::options(),
            ...$this->publicationFormMeta($tender?->status),
        ];
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
                'slug' => $translation['slug'] ?? Str::tajikSlug($translation['title']),
                'organizer' => $translation['organizer'] ?? null,
                'summary' => $translation['summary'] ?? null,
                'description' => $this->sanitizer->clean($translation['description'] ?? null),
                'requirements' => $this->sanitizer->clean($translation['requirements'] ?? null),
                'terms' => $this->sanitizer->clean($translation['terms'] ?? null),
                'seo_title' => $translation['seo_title'] ?? null,
                'seo_description' => $translation['seo_description'] ?? null,
            ])
            ->all();
    }
}
