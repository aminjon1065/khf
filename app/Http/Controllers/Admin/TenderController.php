<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\TenderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenderRequest;
use App\Http\Requests\Admin\UpdateTenderRequest;
use App\Models\Language;
use App\Models\Tender;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenderController extends Controller
{
    /** @var list<string> */
    private const SORTABLE = ['status', 'type', 'published_at', 'deadline_at', 'created_at'];

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), self::SORTABLE, true)
            ? (string) $request->string('sort')
            : 'published_at';
        $direction = (string) $request->string('direction') === 'asc' ? 'asc' : 'desc';

        $tenders = Tender::query()
            ->with('translations')
            ->withCount('bids')
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Tender $tender) => $this->toRow($tender, $locale));

        return Inertia::render('admin/tenders/index', [
            'tenders' => $tenders,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'trashedCount' => Tender::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $locale = app()->getLocale();

        $tenders = Tender::onlyTrashed()
            ->with('translations')
            ->withCount('bids')
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->through(fn (Tender $tender) => $this->toRow($tender, $locale));

        return Inertia::render('admin/tenders/trash', ['tenders' => $tenders]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/tenders/form', $this->formData(null));
    }

    public function store(StoreTenderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $tender = Tender::create([
            'tender_number' => $data['tender_number'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'budget' => $data['budget'] ?? null,
            'lots_count' => $data['lots_count'],
            'published_at' => $data['published_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);
        $tender->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tender created.')]);

        return to_route('admin.tenders.index');
    }

    public function edit(Tender $tender): Response
    {
        $tender->load('translations');

        return Inertia::render('admin/tenders/form', $this->formData($tender));
    }

    public function update(UpdateTenderRequest $request, Tender $tender): RedirectResponse
    {
        $data = $request->validated();

        $tender->update([
            'tender_number' => $data['tender_number'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'budget' => $data['budget'] ?? null,
            'lots_count' => $data['lots_count'],
            'published_at' => $data['published_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
        ]);
        $tender->upsertTranslations($this->translationsPayload($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tender updated.')]);

        return to_route('admin.tenders.index');
    }

    public function destroy(Tender $tender): RedirectResponse
    {
        $tender->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tender moved to trash.')]);

        return to_route('admin.tenders.index');
    }

    public function restore(Tender $tender): RedirectResponse
    {
        $tender->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tender restored.')]);

        return to_route('admin.tenders.trash');
    }

    public function forceDelete(Tender $tender): RedirectResponse
    {
        $tender->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tender permanently deleted.')]);

        return to_route('admin.tenders.trash');
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
                'deadline_at' => $tender->deadline_at?->format('Y-m-d'),
                'translations' => $translations,
            ] : null,
            'locales' => Language::active()
                ->map(fn (Language $language) => ['code' => $language->code, 'native_name' => $language->native_name])
                ->all(),
            'tenderTypes' => TenderType::options(),
            'statuses' => array_map(
                fn (ContentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ContentStatus::cases(),
            ),
            'defaultLocale' => Language::defaultCode(),
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
                'slug' => $translation['slug'] ?? Str::slug($translation['title']),
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
