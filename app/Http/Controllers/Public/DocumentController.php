<?php

namespace App\Http\Controllers\Public;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    /**
     * Public documents registry with search + type filter (ТЗ §6.8).
     */
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->string('search'));
        $type = in_array((string) $request->string('type'), DocumentType::values(), true)
            ? (string) $request->string('type')
            : null;

        $documents = Document::published()
            ->with(['translations', 'media'])
            ->whereHas('translations', fn (Builder $query) => $query->where('locale', $locale))
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'translations',
                fn (Builder $inner) => $inner->where('name', 'like', "%{$search}%"),
            ))
            ->when($type !== null, fn (Builder $query) => $query->where('type', $type))
            ->orderByDesc('document_date')
            ->paginate(20)
            ->withQueryString()
            ->through(function (Document $document) use ($locale): array {
                return [
                    'id' => $document->id,
                    'name' => $document->translation($locale)?->name,
                    'description' => $document->translation($locale)?->description,
                    'type_label' => $document->type->label(),
                    'document_date' => $document->document_date?->format('d.m.Y'),
                    'files' => $document->getMedia(Document::FILES_COLLECTION)->map(fn ($media) => [
                        'id' => $media->id,
                        'name' => $media->file_name,
                        'size' => $media->humanReadableSize,
                        'url' => route('documents.download', [
                            'locale' => $locale,
                            'document' => $document->id,
                            'media' => $media->id,
                        ]),
                    ])->all(),
                ];
            });

        return Inertia::render('public/documents/index', [
            'documents' => $documents,
            'filters' => ['search' => $search, 'type' => $type],
            'types' => DocumentType::options(),
        ]);
    }

    /**
     * Controlled download — files live outside the public webroot (ТЗ §12.4).
     */
    public function download(string $locale, Document $document, int $media): BinaryFileResponse
    {
        abort_unless($document->status->value === 'published', 404);

        $file = $document->getMedia(Document::FILES_COLLECTION)->firstWhere('id', $media);

        abort_if($file === null, 404);

        return response()->download($file->getPath(), $file->file_name);
    }
}
