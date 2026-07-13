<?php

namespace App\Http\Controllers\Admin;

use App\Cms\ContentTypeRegistry;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Document;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\GovService;
use App\Models\Guide;
use App\Models\Incident;
use App\Models\Page;
use App\Models\Post;
use App\Models\Revision;
use App\Services\Admin\RevisionDiffBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RevisionController extends Controller
{
    public function __construct(private RevisionDiffBuilder $diffBuilder) {}

    /**
     * Get revisions for a specific model.
     */
    public function index(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->resolveModel($type, $id);

        $this->authorizeManage($request, $model);

        if (! method_exists($model, 'revisions')) {
            abort(404, 'Model does not support revisions.');
        }

        $revisions = $model->revisions()
            ->with('user:id,name,email')
            ->get()
            ->map(fn (Revision $revision) => [
                'id' => $revision->id,
                'created_at' => $revision->created_at?->toIso8601String(),
                'user' => $revision->user ? [
                    'id' => $revision->user->id,
                    'name' => $revision->user->name,
                ] : null,
            ]);

        return response()->json($revisions);
    }

    /**
     * Show a revision with a diff against the next newer version or the live model.
     */
    public function show(Request $request, Revision $revision): JsonResponse
    {
        /** @var Model $model */
        $model = $revision->revisionable;

        $this->authorizeManage($request, $model);

        if (! method_exists($model, 'revisions')) {
            abort(404, 'Model does not support revisions.');
        }

        $newerRevision = $model->revisions()
            ->where('id', '>', $revision->id)
            ->orderBy('id')
            ->first();

        if ($newerRevision instanceof Revision) {
            $compareLabel = 'Следующая версия';
            $newerPayload = $newerRevision->payload;
        } else {
            $compareLabel = 'Текущая версия';
            $model->loadMissing('translations');
            $newerPayload = [
                'attributes' => $model->getAttributes(),
                'translations' => $model->translations->map(
                    fn (Model $translation) => $translation->getAttributes(),
                )->all(),
            ];
        }

        return response()->json([
            'revision' => [
                'id' => $revision->id,
                'created_at' => $revision->created_at?->toIso8601String(),
                'user' => $revision->user ? [
                    'id' => $revision->user->id,
                    'name' => $revision->user->name,
                ] : null,
            ],
            'compare_label' => $compareLabel,
            'changes' => $this->diffBuilder->diff($revision->payload, $newerPayload),
        ]);
    }

    /**
     * Restore a specific revision.
     */
    public function restore(Request $request, Revision $revision): RedirectResponse
    {
        /** @var Model $model */
        $model = $revision->revisionable;

        if (! method_exists($model, 'restoreRevision')) {
            abort(404, 'Model does not support restoring revisions.');
        }

        $this->authorizeManage($request, $model);

        $model->restoreRevision($revision);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Restored to version from :date', ['date' => $revision->created_at?->format('d.m.Y H:i')])]);

        return back();
    }

    private function authorizeManage(Request $request, Model $model): void
    {
        $definition = app(ContentTypeRegistry::class)->forModel($model);

        abort_unless(
            $definition !== null && $request->user()?->can($definition->managePermission),
            403,
        );
    }

    private function resolveModel(string $type, int $id): Model
    {
        $modelClass = match ($type) {
            'post' => Post::class,
            'page' => Page::class,
            'guide' => Guide::class,
            'document' => Document::class,
            'gallery' => Gallery::class,
            'faq' => Faq::class,
            'service' => GovService::class,
            'incident' => Incident::class,
            'alert' => Alert::class,
            default => abort(404, 'Invalid revisionable type.'),
        };

        return $modelClass::findOrFail($id);
    }
}
