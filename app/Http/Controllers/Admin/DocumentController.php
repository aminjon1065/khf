<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocumentRequest;
use App\Http\Requests\Admin\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    /** @var list<string> */
    private const SORTABLE = ['type', 'document_date', 'status', 'created_at'];

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $sort = in_array((string) $request->string('sort'), self::SORTABLE, true)
            ? (string) $request->string('sort')
            : 'document_date';
        $direction = (string) $request->string('direction') === 'asc' ? 'asc' : 'desc';

        $documents = Document::query()
            ->with(['translations', 'media'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('name', 'like', "%{$search}%"),
            ))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Document $document) => $this->toRow($document, $locale));

        return Inertia::render('admin/documents/index', [
            'documents' => $documents,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'trashedCount' => Document::onlyTrashed()->count(),
        ]);
    }

    public function trash(): Response
    {
        $locale = app()->getLocale();

        $documents = Document::onlyTrashed()
            ->with('translations')
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->through(fn (Document $document) => $this->toRow($document, $locale));

        return Inertia::render('admin/documents/trash', ['documents' => $documents]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/documents/form', $this->formData(null));
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $document = Document::create($this->attributes($data));
        $document->upsertTranslations($this->translationsPayload($data));
        $this->syncFiles($request, $document);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document created.')]);

        return to_route('admin.documents.index');
    }

    public function edit(Document $document): Response
    {
        $document->load(['translations', 'media']);

        return Inertia::render('admin/documents/form', $this->formData($document));
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $data = $request->validated();

        $document->update($this->attributes($data));
        $document->upsertTranslations($this->translationsPayload($data));
        $this->syncFiles($request, $document);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document updated.')]);

        return to_route('admin.documents.index');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document moved to trash.')]);

        return to_route('admin.documents.index');
    }

    public function restore(Document $document): RedirectResponse
    {
        $document->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document restored.')]);

        return to_route('admin.documents.trash');
    }

    public function forceDelete(Document $document): RedirectResponse
    {
        $document->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document permanently deleted.')]);

        return to_route('admin.documents.trash');
    }

    private function syncFiles(Request $request, Document $document): void
    {
        foreach ($request->file('files') ?? [] as $file) {
            $document->addMedia($file)->toMediaCollection(Document::FILES_COLLECTION);
        }

        $removeIds = array_map('intval', $request->input('remove_files', []) ?? []);

        if ($removeIds !== []) {
            $document->getMedia(Document::FILES_COLLECTION)
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
            'type' => $data['type'],
            'source' => $data['source'] ?? null,
            'document_date' => $data['document_date'] ?? null,
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Document $document, string $locale): array
    {
        return [
            'id' => $document->id,
            'name' => $document->translation($locale)?->name ?? '—',
            'type' => $document->type->value,
            'type_label' => $document->type->label(),
            'status' => $document->status->value,
            'status_label' => $document->status->label(),
            'locales' => $document->translatedLocales(),
            'document_date' => $document->document_date?->format('d.m.Y'),
            'files_count' => $document->relationLoaded('media') ? $document->getMedia(Document::FILES_COLLECTION)->count() : 0,
            'deleted_at' => $document->deleted_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Document $document): array
    {
        $translations = [];
        $files = [];

        if ($document) {
            foreach ($document->translations as $translation) {
                $translations[$translation->locale] = [
                    'name' => $translation->name,
                    'description' => $translation->description,
                ];
            }

            $files = $document->getMedia(Document::FILES_COLLECTION)
                ->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'size' => $media->humanReadableSize,
                    'url' => route('documents.download', [
                        'locale' => app()->getLocale(),
                        'document' => $document->id,
                        'media' => $media->id,
                    ]),
                ])
                ->all();
        }

        return [
            'document' => $document ? [
                'id' => $document->id,
                'type' => $document->type->value,
                'source' => $document->source,
                'document_date' => $document->document_date?->format('Y-m-d'),
                'status' => $document->status->value,
                'sort_order' => $document->sort_order,
                'translations' => $translations,
                'files' => $files,
            ] : null,
            'types' => DocumentType::options(),
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
    private function translationsPayload(array $data): array
    {
        return collect($data['translations'] ?? [])
            ->filter(fn (array $translation) => filled($translation['name'] ?? null))
            ->map(fn (array $translation) => [
                'name' => $translation['name'],
                'description' => $translation['description'] ?? null,
            ])
            ->all();
    }
}
