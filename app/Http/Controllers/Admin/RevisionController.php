<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Guide;
use App\Models\Page;
use App\Models\Post;
use App\Models\Revision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RevisionController extends Controller
{
    /**
     * Get revisions for a specific model.
     */
    public function index(Request $request, string $type, int $id): JsonResponse
    {
        // Map common type names to fully qualified model classes
        $modelClass = match ($type) {
            'post' => Post::class,
            'page' => Page::class,
            'guide' => Guide::class,
            'document' => Document::class,
            default => abort(404, 'Invalid revisionable type.'),
        };

        /** @var Model $model */
        $model = $modelClass::findOrFail($id);

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
     * Restore a specific revision.
     */
    public function restore(Revision $revision): RedirectResponse
    {
        /** @var Model $model */
        $model = $revision->revisionable;

        if (! method_exists($model, 'restoreRevision')) {
            abort(404, 'Model does not support restoring revisions.');
        }

        $model->restoreRevision($revision);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Restored to version from :date', ['date' => $revision->created_at?->format('d.m.Y H:i')])]);

        return back();
    }
}
