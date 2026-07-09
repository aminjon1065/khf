<?php

namespace App\Http\Controllers\Admin;

use App\Cms\ContentTypeRegistry;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\Admin\ModerationQueueService;
use App\Services\Cms\EditorialWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function index(Request $request, ModerationQueueService $queue, EditorialWorkflow $workflow): Response
    {
        abort_unless($workflow->canViewModerationQueue($request->user()), 403);

        return Inertia::render('admin/moderation/index', [
            'items' => $queue->items(),
            'total' => $queue->count(),
            'canPublish' => $workflow->canPublish($request->user()),
        ]);
    }

    public function publish(
        Request $request,
        string $type,
        int $id,
        ContentTypeRegistry $contentTypes,
        EditorialWorkflow $workflow,
    ): RedirectResponse {
        abort_unless($workflow->canPublish($request->user()), 403);

        $definition = $contentTypes->has($type) ? $contentTypes->get($type) : null;

        abort_if($definition === null, 404);

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition->modelClass;
        $model = $modelClass::query()->findOrFail($id);

        abort_if(
            ($model->getCasts()['status'] ?? null) !== ContentStatus::class,
            404,
        );

        /** @var ContentStatus $current */
        $current = $model->status;

        abort_if($current !== ContentStatus::Moderation, 422);
        abort_unless(
            $workflow->canTransition($request->user(), $current, ContentStatus::Published),
            403,
        );

        $payload = $workflow->normalizePublication([
            'status' => ContentStatus::Published->value,
            'published_at' => $model->getAttributes()['published_at'] ?? null,
        ]);

        $model->update(collect($payload)->only($model->getFillable())->all());

        $model->refresh();

        if (method_exists($model, 'capturePublishedSnapshot')) {
            $model->capturePublishedSnapshot();
        }

        if ($model instanceof Post) {
            $model->saveRevision();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Материал опубликован.']);

        return redirect()->route('admin.moderation.index');
    }
}
