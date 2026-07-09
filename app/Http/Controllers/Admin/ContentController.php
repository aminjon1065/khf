<?php

namespace App\Http\Controllers\Admin;

use App\Cms\ContentTypeDefinition;
use App\Cms\ContentTypeRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportContentRequest;
use App\Services\Admin\ContentBrowserService;
use App\Services\Admin\ContentExportService;
use App\Services\Admin\ContentImportService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContentController extends Controller
{
    public function __construct(
        private ContentTypeRegistry $contentTypes,
        private ContentBrowserService $browser,
        private ContentExportService $exporter,
        private ContentImportService $importer,
    ) {}

    public function hub(Request $request): Response
    {
        return Inertia::render('admin/content/hub', [
            'types' => $this->browser->hubTypesFor($request->user()),
        ]);
    }

    public function index(Request $request, string $type): Response
    {
        $definition = $this->resolveType($request, $type);

        return Inertia::render('admin/content/index', $this->browser->indexProps($definition, $request));
    }

    public function bulkDestroy(Request $request, string $type): RedirectResponse
    {
        $definition = $this->resolveType($request, $type);

        abort_unless($definition->hasFeature('soft_deletes'), 404);

        /** @var array{ids: list<int>} $validated */
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition->modelClass;

        $modelClass::query()
            ->whereIn('id', $validated['ids'])
            ->get()
            ->each->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Выбранные материалы перемещены в корзину.',
        ]);

        return redirect()->route($definition->browserRoute(), $definition->handle);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        $definition = $this->resolveType($request, $type);
        abort_unless($definition->hasFeature('translations'), 404);

        $filters = $this->browser->filtersFromRequest($definition, $request);
        $ids = $this->normaliseIds($request);

        $format = (string) $request->string('format', 'json');

        return match ($format) {
            'csv' => $this->exporter->toCsvDownload($definition, $filters, $ids),
            default => $this->exporter->toJsonDownload($definition, $filters, $ids),
        };
    }

    public function import(ImportContentRequest $request, string $type): RedirectResponse
    {
        $definition = $this->resolveType($request, $type);
        abort_unless($definition->hasFeature('translations'), 404);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $contents = $file->get();
        $extension = strtolower($file->getClientOriginalExtension());

        $stats = match ($extension) {
            'csv', 'txt' => $this->importer->importCsv(
                $definition,
                $contents,
                $request->shouldUpdateExisting(),
            ),
            default => $this->importer->importJson(
                $definition,
                json_decode($contents, true, flags: JSON_THROW_ON_ERROR),
                $request->shouldUpdateExisting(),
            ),
        };

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Импорт завершён: создано {$stats['created']}, обновлено {$stats['updated']}, пропущено {$stats['skipped']}.",
        ]);

        return redirect()->route($definition->browserRoute(), $definition->handle);
    }

    /**
     * @return list<int>|null
     */
    private function normaliseIds(Request $request): ?array
    {
        $ids = $request->input('ids');

        if (! is_array($ids) || $ids === []) {
            return null;
        }

        return array_values(array_map(intval(...), $ids));
    }

    private function resolveType(Request $request, string $type): ContentTypeDefinition
    {
        abort_unless($this->contentTypes->has($type), 404);

        $definition = $this->contentTypes->get($type);

        abort_unless($request->user()?->can($definition->managePermission), 403);

        return $definition;
    }
}
