<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentType;
use App\Http\Controllers\Admin\Concerns\BuildsCmsEntryFormProps;
use App\Http\Controllers\Admin\Concerns\BuildsCmsFormData;
use App\Http\Controllers\Admin\Concerns\ManagesSoftDeletableContent;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Admin\Concerns\RedirectsToContentBrowser;
use App\Http\Controllers\Admin\Concerns\SavesContentRevisions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocumentRequest;
use App\Http\Requests\Admin\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    use BuildsCmsEntryFormProps;
    use BuildsCmsFormData;
    use ManagesSoftDeletableContent;
    use ProvidesBlueprintForm;
    use RedirectsToContentBrowser;
    use SavesContentRevisions;

    public function index(): RedirectResponse
    {
        return $this->redirectToContentBrowser('document');
    }

    public function trash(): RedirectResponse
    {
        return $this->redirectToContentBrowserTrash('document');
    }

    public function create(): Response
    {
        return Inertia::render('admin/content/form', $this->formData(null));
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $document = Document::create($this->attributes($data));
        $document->upsertTranslations($this->translationsPayload($data));
        $document->tags()->sync($data['tag_ids'] ?? []);
        $this->syncFiles($request, $document);
        $this->saveContentRevision($document);
        $this->flashContentSaved(__('Document created.'));

        return $this->toContentBrowser('document');
    }

    public function edit(Document $document): Response
    {
        $document->load(['translations', 'media', 'tags.translations']);

        return Inertia::render('admin/content/form', $this->formData($document));
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $data = $request->validated();

        $document->update($this->attributes($data));
        $document->upsertTranslations($this->translationsPayload($data));
        $document->tags()->sync($data['tag_ids'] ?? []);
        $this->syncFiles($request, $document);
        $this->saveContentRevision($document);
        $this->flashContentSaved(__('Document updated.'));

        return $this->toContentBrowser('document');
    }

    public function destroy(Document $document): RedirectResponse
    {
        return $this->moveToTrash($document, 'admin.content.index', __('Document moved to trash.'), 'document');
    }

    public function restore(Document $document): RedirectResponse
    {
        return $this->restoreFromTrash($document, 'admin.content.index', __('Document restored.'), ['type' => 'document', 'trashed' => 1]);
    }

    public function forceDelete(Document $document): RedirectResponse
    {
        return $this->permanentlyDelete($document, 'admin.content.index', __('Document permanently deleted.'), ['type' => 'document', 'trashed' => 1]);
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
    private function formData(?Document $document): array
    {
        $locale = app()->getLocale();
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

        return $this->contentEntryFormProps(
            'document',
            $document ? [
                'id' => $document->id,
                'type' => $document->type->value,
                'source' => $document->source,
                'document_date' => $document->document_date?->format('Y-m-d'),
                'status' => $document->status->value,
                'sort_order' => $document->sort_order,
                'tag_ids' => $document->tags->pluck('id')->all(),
                'translations' => $translations,
            ] : null,
            [
                'type' => DocumentType::options(),
                'tag_ids' => Tag::query()
                    ->with('translations')
                    ->get()
                    ->map(fn (Tag $tag) => ['id' => $tag->id, 'name' => $tag->translation($locale)?->name ?? "#{$tag->id}"])
                    ->all(),
            ],
            [
                'existingFiles' => $files,
            ],
        );
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
